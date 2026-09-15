(function (root) {
    'use strict';
    function date(value) {
        const text = String(value || '').trim().slice(0, 10);
        if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) return '';
        const parsed = new Date(text + 'T00:00:00Z');
        return Number.isFinite(parsed.getTime()) && parsed.toISOString().slice(0, 10) === text ? text : '';
    }
    function days(a, b) { return (Date.parse(b + 'T00:00:00Z') - Date.parse(a + 'T00:00:00Z')) / 86400000; }
    function analyze(rows, admission, discharge) {
        const errors = [], periods = [];
        rows.forEach(function (row) {
            if (!row.ini && !row.fim && !row.acomod) return;
            const ini = date(row.ini), fim = date(row.fim);
            if (!ini || !fim || !row.acomod) { errors.push('Preencha acomodação, início e fim de cada período.'); return; }
            if (fim <= ini) { errors.push('A data final deve ser posterior à inicial.'); return; }
            if (admission && ini < admission) errors.push('A prorrogação não pode começar antes da internação.');
            if (discharge && fim > discharge) errors.push('A prorrogação não pode ultrapassar a alta.');
            periods.push({ini:ini, fim:fim});
        });
        periods.sort((a,b) => a.ini.localeCompare(b.ini));
        let last = '';
        periods.forEach(function (p) { if (last && p.ini < last) errors.push('Existem períodos sobrepostos.'); last = last > p.fim ? last : p.fim; });
        let cursor = admission || (periods[0] && periods[0].ini) || '';
        const end = discharge || last || cursor, gaps = [];
        periods.forEach(function (p) {
            if (!cursor || cursor >= end || p.fim <= cursor) return;
            if (p.ini > cursor) gaps.push({ini:cursor, fim:p.ini < end ? p.ini : end});
            cursor = p.fim < end ? p.fim : end;
        });
        if (cursor && cursor < end) gaps.push({ini:cursor, fim:end});
        return {errors:[...new Set(errors)], gaps:gaps,
            missingDays:gaps.reduce((sum,g)=>sum+days(g.ini,g.fim),0),
            next:gaps[0] || (discharge ? null : {ini:cursor, fim:''})};
    }
    const api = {date:date, days:days, analyze:analyze};
    if (typeof module !== 'undefined' && module.exports) { module.exports=api; return; }
    root.FullCareProrrog = api;
    function config() { const node=document.getElementById('prorrog-timeline-config'); return node ? JSON.parse(node.textContent) : {history:[]}; }
    function container() { return document.getElementById('fieldsContainer') || document.getElementById('prorContainer'); }
    function bounds() {
        const c=config();
        const admissionField=document.getElementById('data_intern_int_dt') || document.getElementById('data_intern_int');
        const inlineAlta=document.getElementById('prorrog_data_alta_alt');
        const altaFlag=document.getElementById('prorrog_gerar_alta');
        return {admission:date(admissionField && admissionField.value) || c.admission || '',
            discharge:altaFlag && altaFlag.value==='s' && inlineAlta && date(inlineAlta.value) || c.discharge || ''};
    }
    function elements(row) {
        return {ini:row.querySelector('[name="prorrog1_ini_pror"], [name$="[ini]"]'),
            fim:row.querySelector('[name="prorrog1_fim_pror"], [name$="[fim]"]'),
            acomod:row.querySelector('[name="acomod1_pror"], [name$="[acomod]"]')};
    }
    function rows(exclude) {
        const host=container();
        return (config().history || []).concat(host ? Array.from(host.querySelectorAll('.field-container, .pror-row')).filter(r=>r!==exclude).map(function(r){
            const e=elements(r); return {ini:e.ini && e.ini.value, fim:e.fim && e.fim.value, acomod:e.acomod && e.acomod.value};
        }) : []);
    }
    api.suggest=function(exclude) { const b=bounds(); return analyze(rows(exclude),b.admission,b.discharge).next; };
    api.refresh=function() {
        const host=container(); if (!host) return;
        const b=bounds(), result=analyze(rows(),b.admission,b.discharge);
        let status=document.getElementById('prorrog-timeline-status');
        if (!status) { status=document.createElement('div'); status.id='prorrog-timeline-status'; status.className='alert alert-info mt-2'; status.setAttribute('role','status'); host.after(status); }
        const br=d=>d.split('-').reverse().join('/');
        status.textContent=result.errors.length ? result.errors.join(' ') : result.gaps.length ?
            'Diárias pendentes: '+result.missingDays+' — '+result.gaps.map(g=>br(g.ini)+' → '+br(g.fim)).join(' • ') :
            b.discharge ? 'Diárias completas até a alta.' : 'Sem lacunas no período lançado.';
        host.querySelectorAll('.field-container, .pror-row').forEach(function(row){
            const e=elements(row);
            if (e.ini && b.admission) e.ini.min=b.admission;
            if (e.ini && b.discharge) e.ini.max=b.discharge;
            if (e.fim && b.discharge) e.fim.max=b.discharge;
        });
        return result;
    };
    document.addEventListener('DOMContentLoaded', function () {
        const host=container(); if (!host) return;
        api.refresh();
        document.addEventListener('input',api.refresh);
        document.addEventListener('change',api.refresh);
        new MutationObserver(api.refresh).observe(host,{childList:true,subtree:true});
        const form=host.closest('form');
        if (form) form.addEventListener('submit',function(event){
            const flag=document.getElementById('select_prorrog'); if(flag && flag.value!=='s')return;
            const result=api.refresh();
            if(result.errors.length){event.preventDefault();event.stopImmediatePropagation();
                const msg=result.errors.join('\n');
                if(typeof root.openProrrogError==='function')root.openProrrogError(msg);else root.alert(msg);
            }
        },true);
    });
})(typeof window !== 'undefined' ? window : this);
