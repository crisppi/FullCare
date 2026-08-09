import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:mobile_app/src/mobile_api.dart';
import 'package:mobile_app/src/models.dart';
import 'package:shared_preferences/shared_preferences.dart';

const _recentAdmissionsKey = 'fullcare_recent_admissions';
const _darkModeKey = 'fullcare_dark_mode';
const _textScaleKey = 'fullcare_text_scale';

class _RecentAdmission {
  const _RecentAdmission({
    required this.id,
    required this.patientName,
    required this.hospitalName,
  });

  factory _RecentAdmission.fromJson(Map<String, dynamic> json) {
    return _RecentAdmission(
      id: json['id'] as int? ?? 0,
      patientName: json['patient_name'] as String? ?? '',
      hospitalName: json['hospital_name'] as String? ?? '',
    );
  }

  final int id;
  final String patientName;
  final String hospitalName;

  Map<String, dynamic> toJson() => {
    'id': id,
    'patient_name': patientName,
    'hospital_name': hospitalName,
  };
}

int _admissionDays(String value) {
  final date = DateTime.tryParse(value);
  if (date == null) return 0;
  final today = DateTime.now();
  final start = DateTime(date.year, date.month, date.day);
  final end = DateTime(today.year, today.month, today.day);
  return end.difference(start).inDays + 1;
}

String _friendlyError(Object error) {
  final message = error.toString().replaceFirst('Exception: ', '').trim();
  final lower = message.toLowerCase();
  if (lower.contains('conectar') ||
      lower.contains('conexao') ||
      lower.contains('timeout')) {
    return 'Não foi possível conectar. Verifique a internet e toque em tentar novamente.';
  }
  if (lower.contains('sessao') || lower.contains('não autorizado')) {
    return 'Sua sessão expirou. Entre novamente para continuar.';
  }
  if (lower.contains('rota nao encontrada')) {
    return 'Este recurso ainda não está disponível no servidor. Atualize e tente novamente.';
  }
  return message.endsWith('.')
      ? '$message Tente novamente.'
      : '$message. Tente novamente.';
}

Future<List<_RecentAdmission>> _loadRecentAdmissions() async {
  final prefs = await SharedPreferences.getInstance();
  final raw = prefs.getString(_recentAdmissionsKey);
  if (raw == null || raw.isEmpty) return const [];
  try {
    return (jsonDecode(raw) as List<dynamic>)
        .map((item) => _RecentAdmission.fromJson(item as Map<String, dynamic>))
        .where((item) => item.id > 0)
        .toList();
  } catch (_) {
    return const [];
  }
}

Future<void> _rememberAdmission(AdmissionItem admission) async {
  final prefs = await SharedPreferences.getInstance();
  final recent = await _loadRecentAdmissions();
  final updated =
      <_RecentAdmission>[
        _RecentAdmission(
          id: admission.id,
          patientName: admission.patientName,
          hospitalName: admission.hospitalName,
        ),
        ...recent.where((item) => item.id != admission.id),
      ].take(6).toList();
  await Future.wait([
    prefs.setString(
      _recentAdmissionsKey,
      jsonEncode(updated.map((item) => item.toJson()).toList()),
    ),
  ]);
}

class FullCareMobileApp extends StatefulWidget {
  const FullCareMobileApp({super.key});

  @override
  State<FullCareMobileApp> createState() => _FullCareMobileAppState();
}

class _FullCareMobileAppState extends State<FullCareMobileApp> {
  final MobileApi _api = MobileApi();
  SessionUser? _user;
  bool _loading = true;
  bool _darkMode = false;
  double _textScale = .9;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final preferences = await SharedPreferences.getInstance();
    _darkMode = preferences.getBool(_darkModeKey) ?? false;
    _textScale = (preferences.getDouble(_textScaleKey) ?? .9).clamp(.8, 1.1);
    await _api.loadSavedToken();
    if (_api.hasToken) {
      try {
        final user = await _api.me();
        if (!mounted) return;
        setState(() {
          _user = user;
        });
      } catch (_) {
        await _api.clearSession();
      }
    }

    if (!mounted) return;
    setState(() {
      _loading = false;
    });
  }

  Future<void> _handleLogin(String email, String password) async {
    final user = await _api.login(email: email, password: password);
    if (!mounted) return;
    setState(() {
      _user = user;
    });
  }

  Future<void> _handleLogout() async {
    await _api.clearSession();
    if (!mounted) return;
    setState(() {
      _user = null;
    });
  }

  Future<void> _setDarkMode(bool value) async {
    setState(() => _darkMode = value);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(_darkModeKey, value);
  }

  Future<void> _setTextScale(double value) async {
    setState(() => _textScale = value);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setDouble(_textScaleKey, value);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'FullCare Audit',
      themeMode: _darkMode ? ThemeMode.dark : ThemeMode.light,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2D63A6),
          primary: const Color(0xFF2D63A6),
          secondary: const Color(0xFF5E2363),
          surface: Colors.white,
        ),
        scaffoldBackgroundColor: const Color(0xFFF2F6FC),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF2D63A6),
          foregroundColor: Colors.white,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: Colors.white,
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 14,
            vertical: 12,
          ),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFD8E3F0)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFFD8E3F0)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: Color(0xFF2D63A6), width: 1.4),
          ),
        ),
      ),
      darkTheme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF5A9BD5),
          brightness: Brightness.dark,
          primary: const Color(0xFF8AC3F0),
          secondary: const Color(0xFFD4A5D8),
        ),
        scaffoldBackgroundColor: const Color(0xFF101822),
        cardTheme: const CardThemeData(color: Color(0xFF1A2633)),
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF183B61),
          foregroundColor: Colors.white,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFF1B2937),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
        ),
      ),
      builder: (context, child) {
        final media = MediaQuery.of(context);
        return MediaQuery(
          data: media.copyWith(
            textScaler: TextScaler.linear(_textScale),
            boldText: false,
          ),
          child: child!,
        );
      },
      home:
          _loading
              ? const Scaffold(body: Center(child: CircularProgressIndicator()))
              : (_user == null
                  ? LoginPage(onLogin: _handleLogin)
                  : HomeHubPage(
                    api: _api,
                    user: _user!,
                    onLogout: _handleLogout,
                    darkMode: _darkMode,
                    textScale: _textScale,
                    onDarkModeChanged: _setDarkMode,
                    onTextScaleChanged: _setTextScale,
                  )),
    );
  }
}

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, required this.onLogin});

  final Future<void> Function(String email, String password) onLogin;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _submitting = false;
  bool _acceptedPrivacy = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_acceptedPrivacy) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Leia e aceite a Politica de Privacidade para entrar.'),
        ),
      );
      return;
    }

    setState(() => _submitting = true);
    try {
      await widget.onLogin(
        _emailController.text.trim(),
        _passwordController.text,
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final privacyTextStyle = Theme.of(context).textTheme.bodySmall?.copyWith(
      color: const Color(0xFF425466),
      height: 1.2,
    );

    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF2D63A6), Color(0xFF92BEE2), Color(0xFF5E2363)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: LayoutBuilder(
            builder:
                (context, constraints) => SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 16,
                  ),
                  child: ConstrainedBox(
                    constraints: BoxConstraints(
                      minHeight:
                          constraints.maxHeight > 32
                              ? constraints.maxHeight - 32
                              : 0,
                    ),
                    child: Center(
                      child: ConstrainedBox(
                        constraints: const BoxConstraints(maxWidth: 420),
                        child: Card(
                          elevation: 0,
                          color: Colors.white.withValues(alpha: 0.95),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(28),
                          ),
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(22, 18, 22, 22),
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Center(
                                  child: Image.asset(
                                    'assets/branding/fullcare_footer_logo.png',
                                    width: 76,
                                    height: 76,
                                    fit: BoxFit.contain,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                Text(
                                  'FullCare Audit',
                                  style: Theme.of(context)
                                      .textTheme
                                      .headlineSmall
                                      ?.copyWith(fontWeight: FontWeight.w700),
                                ),
                                const SizedBox(height: 8),
                                const Text(
                                  'Acesso seguro para gestao de auditoria e controles operacionais.',
                                ),
                                const SizedBox(height: 16),
                                TextField(
                                  controller: _emailController,
                                  keyboardType: TextInputType.emailAddress,
                                  autofillHints: const [AutofillHints.email],
                                  textInputAction: TextInputAction.next,
                                  decoration: const InputDecoration(
                                    labelText: 'E-mail',
                                  ),
                                ),
                                const SizedBox(height: 12),
                                TextField(
                                  controller: _passwordController,
                                  obscureText: true,
                                  autofillHints: const [AutofillHints.password],
                                  textInputAction: TextInputAction.done,
                                  onSubmitted:
                                      (_) => _submitting ? null : _submit(),
                                  decoration: const InputDecoration(
                                    labelText: 'Senha',
                                  ),
                                ),
                                const SizedBox(height: 14),
                                Container(
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF5F8FC),
                                    borderRadius: BorderRadius.circular(16),
                                    border: Border.all(
                                      color: const Color(0xFFD8E3F0),
                                    ),
                                  ),
                                  padding: const EdgeInsets.fromLTRB(
                                    12,
                                    10,
                                    12,
                                    8,
                                  ),
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          SizedBox(
                                            width: 24,
                                            height: 24,
                                            child: Checkbox(
                                              value: _acceptedPrivacy,
                                              onChanged:
                                                  (value) => setState(
                                                    () =>
                                                        _acceptedPrivacy =
                                                            value ?? false,
                                                  ),
                                            ),
                                          ),
                                          const SizedBox(width: 10),
                                          Expanded(
                                            child: Text(
                                              'Li e aceito a Politica de Privacidade.',
                                              style: privacyTextStyle,
                                            ),
                                          ),
                                        ],
                                      ),
                                      Align(
                                        alignment: Alignment.centerLeft,
                                        child: TextButton(
                                          onPressed:
                                              () => _showPrivacyPolicy(context),
                                          style: TextButton.styleFrom(
                                            minimumSize: const Size(0, 32),
                                            padding: const EdgeInsets.only(
                                              left: 34,
                                              right: 8,
                                            ),
                                            tapTargetSize:
                                                MaterialTapTargetSize
                                                    .shrinkWrap,
                                          ),
                                          child: const Text(
                                            'Ver Politica de Privacidade',
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(height: 16),
                                FilledButton(
                                  style: FilledButton.styleFrom(
                                    backgroundColor: const Color(0xFF5E2363),
                                    minimumSize: const Size.fromHeight(52),
                                  ),
                                  onPressed: _submitting ? null : _submit,
                                  child:
                                      _submitting
                                          ? const SizedBox(
                                            width: 20,
                                            height: 20,
                                            child: CircularProgressIndicator(
                                              strokeWidth: 2,
                                              color: Colors.white,
                                            ),
                                          )
                                          : const Text('Entrar'),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
          ),
        ),
      ),
    );
  }

  Future<void> _showPrivacyPolicy(BuildContext context) async {
    await showDialog<void>(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('Politica de Privacidade'),
            content: const SingleChildScrollView(
              child: Text(
                'Ao acessar este aplicativo, voce concorda com o tratamento dos seus dados pessoais de acordo com a Lei Geral de Protecao de Dados Pessoais, Lei no 13.709/2018.\n\n'
                'Coletamos e utilizamos apenas as informacoes necessarias para identificar o usuario, permitir o acesso seguro ao sistema, organizar gestao de auditoria, controles operacionais, conformidade e evidencias internas.\n\n'
                'Os dados informados podem incluir nome, e-mail, dados de login, informacoes profissionais, perfil de acesso, permissoes e registros administrativos necessarios para auditoria e controle interno. Essas informacoes sao utilizadas exclusivamente para as finalidades do sistema e nao sao vendidas ou compartilhadas para fins comerciais.\n\n'
                'Adotamos medidas tecnicas e administrativas para proteger os dados contra acessos nao autorizados, perda, alteracao, divulgacao indevida ou qualquer forma de tratamento inadequado. O acesso as informacoes e restrito a usuarios autorizados, conforme seu perfil e necessidade de uso.\n\n'
                'O titular dos dados podera solicitar, quando aplicavel, confirmacao de tratamento, acesso, correcao, atualizacao, bloqueio, exclusao ou informacoes sobre o uso de seus dados, conforme previsto na LGPD.\n\n'
                'Ao continuar, voce declara estar ciente desta Politica de Privacidade e autoriza o uso dos seus dados para as finalidades descritas.',
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Fechar'),
              ),
              FilledButton(
                onPressed: () {
                  setState(() => _acceptedPrivacy = true);
                  Navigator.of(context).pop();
                },
                child: const Text('Aceitar'),
              ),
            ],
          ),
    );
  }
}

class HomeHubPage extends StatefulWidget {
  const HomeHubPage({
    super.key,
    required this.api,
    required this.user,
    required this.onLogout,
    required this.darkMode,
    required this.textScale,
    required this.onDarkModeChanged,
    required this.onTextScaleChanged,
  });

  final MobileApi api;
  final SessionUser user;
  final Future<void> Function() onLogout;
  final bool darkMode;
  final double textScale;
  final Future<void> Function(bool value) onDarkModeChanged;
  final Future<void> Function(double value) onTextScaleChanged;

  @override
  State<HomeHubPage> createState() => _HomeHubPageState();
}

class _HomeHubPageState extends State<HomeHubPage> {
  DashboardSummary? _summary;
  bool _loadingDashboard = true;
  String? _dashboardError;

  @override
  void initState() {
    super.initState();
    _refreshDashboard();
  }

  Future<void> _refreshDashboard() async {
    if (mounted) {
      setState(() {
        _loadingDashboard = true;
        _dashboardError = null;
      });
    }

    try {
      final summary = await widget.api.dashboardSummary();
      if (!mounted) return;
      setState(() {
        _summary = summary;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _dashboardError = _friendlyError(error);
      });
    } finally {
      if (mounted) setState(() => _loadingDashboard = false);
    }
  }

  Future<void> _openAdmissions(String title, {String filter = ''}) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder:
            (_) => AdmissionsHomePage(
              api: widget.api,
              title: title,
              filter: filter,
            ),
      ),
    );
    await _refreshDashboard();
  }

  Future<void> _openLongStay() async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => LongStayCasesPage(api: widget.api)),
    );
    await _refreshDashboard();
  }

  Future<void> _openAdverseEvents() async {
    await Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => AdverseEventCasesPage(api: widget.api)),
    );
    await _refreshDashboard();
  }

  @override
  Widget build(BuildContext context) {
    final isIOS = Theme.of(context).platform == TargetPlatform.iOS;
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        titleSpacing: 16,
        title: Row(
          children: [
            Image.asset(
              'assets/branding/fullcare_footer_logo.png',
              width: 28,
              height: 28,
              fit: BoxFit.contain,
            ),
            const SizedBox(width: 10),
            Text('FullCare', style: TextStyle(fontSize: isIOS ? 17 : 20)),
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Atualizar painel',
            onPressed: _loadingDashboard ? null : _refreshDashboard,
            icon: const Icon(Icons.refresh),
          ),
          IconButton(
            tooltip: 'Sair',
            onPressed: () async {
              await widget.onLogout();
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refreshDashboard,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 7),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF2D63A6), Color(0xFF4D8CC6)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(22),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Olá, ${widget.user.name}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: isIOS ? 11.5 : 13,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Visão geral',
                    style: TextStyle(
                      fontSize: isIOS ? 14 : 17,
                      fontWeight: FontWeight.w800,
                      color: Theme.of(context).colorScheme.onSurface,
                    ),
                  ),
                ),
                if (_loadingDashboard)
                  const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
              ],
            ),
            const SizedBox(height: 7),
            if (_dashboardError != null && _summary == null)
              Container(
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF4E5),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFF1D39B)),
                ),
                child: Row(
                  children: [
                    const Icon(
                      Icons.cloud_off_outlined,
                      color: Color(0xFF8B5E1A),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _dashboardError!,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    TextButton(
                      onPressed: _refreshDashboard,
                      child: const Text('Tentar'),
                    ),
                  ],
                ),
              )
            else
              Column(
                children: [
                  SizedBox(
                    height: 68,
                    child: _DashboardMetricCard(
                      value: _summary?.activeAdmissions,
                      label: 'Internações ativas',
                      helper: 'Em acompanhamento',
                      icon: Icons.local_hospital_outlined,
                      color: const Color(0xFF2D63A6),
                      onTap: () => _openAdmissions('Internações ativas'),
                      wide: true,
                    ),
                  ),
                  const SizedBox(height: 8),
                  GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    mainAxisSpacing: 8,
                    crossAxisSpacing: 8,
                    childAspectRatio: 2.05,
                    children: [
                      _DashboardMetricCard(
                        value: _summary?.pendingVisits,
                        label: 'Visitas pendentes',
                        helper: 'Sem visita hoje',
                        icon: Icons.fact_check_outlined,
                        color: const Color(0xFF9A6700),
                        onTap:
                            () => _openAdmissions(
                              'Visitas pendentes',
                              filter: 'pending-visits',
                            ),
                      ),
                      _DashboardMetricCard(
                        value: _summary?.extensionsDue,
                        label: 'Prorrogações próximas',
                        helper: 'Até 3 dias',
                        icon: Icons.event_repeat,
                        color: const Color(0xFF5E2363),
                        onTap:
                            () => _openAdmissions(
                              'Prorrogações próximas',
                              filter: 'extensions-due',
                            ),
                      ),
                      _DashboardMetricCard(
                        value: _summary?.longStayCases,
                        label: 'Longa permanência',
                        icon: Icons.hotel_outlined,
                        color: const Color(0xFF0F766E),
                        onTap: _openLongStay,
                      ),
                      _DashboardMetricCard(
                        value: _summary?.openAdverseEvents,
                        label: 'Eventos em aberto',
                        icon: Icons.warning_amber_rounded,
                        color: const Color(0xFFB54708),
                        onTap: _openAdverseEvents,
                      ),
                    ],
                  ),
                ],
              ),
            const SizedBox(height: 14),
            Text(
              'Rotinas FullCare',
              style: TextStyle(
                fontSize: isIOS ? 14 : 17,
                fontWeight: FontWeight.w800,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 7),
            _FullCareMenuRow(
              icon: Icons.menu_book_outlined,
              title: 'Censo de internações',
              subtitle: 'Lista de pacientes internados e prestadores',
              iconBackgroundColor: const Color(0xFFE8F1FB),
              accentColor: const Color(0xFF2D63A6),
              onTap: () => _openAdmissions('Censo de internações'),
            ),
            _FullCareMenuRow(
              icon: Icons.edit_note_outlined,
              title: 'Auditoria e visita',
              subtitle: 'Visitas, TUSS, prorrogações e alta hospitalar',
              iconBackgroundColor: const Color(0xFFF2EAF7),
              accentColor: const Color(0xFF5E2363),
              onTap: () => _openAdmissions('Auditoria e visita'),
            ),
            const SizedBox(height: 12),
            Text(
              'App',
              style: TextStyle(
                fontSize: isIOS ? 11 : 13,
                fontWeight: FontWeight.w800,
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 6),
            DecoratedBox(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surfaceContainer,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: Theme.of(context).colorScheme.outlineVariant,
                ),
              ),
              child: _SecondaryMenuRow(
                icon: Icons.privacy_tip_outlined,
                title: 'Política de Privacidade',
                onTap: () => _showAboutAndPrivacy(context),
              ),
            ),
            const SizedBox(height: 6),
            DecoratedBox(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surfaceContainer,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: Theme.of(context).colorScheme.outlineVariant,
                ),
              ),
              child: _SecondaryMenuRow(
                icon: Icons.display_settings_outlined,
                title: 'Aparência e leitura',
                onTap: _showDisplaySettings,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showAboutAndPrivacy(BuildContext context) async {
    await showDialog<void>(
      context: context,
      builder:
          (context) => AlertDialog(
            title: const Text('FullCare Audit'),
            content: const SingleChildScrollView(
              child: Text(
                'Aplicativo de gestao de auditoria para usuarios autorizados do FullCare.\n\n'
                'O app utiliza login individual, sessao autenticada e acesso restrito aos recursos administrativos permitidos.\n\n'
                'Politica de Privacidade:\n'
                'https://accertconsult.com.br/politica-privacidade-fullcare-mobile.html',
              ),
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.of(context).pop(),
                child: const Text('Fechar'),
              ),
            ],
          ),
    );
  }

  Future<void> _showDisplaySettings() async {
    var darkMode = widget.darkMode;
    var textScale = widget.textScale;
    await showDialog<void>(
      context: context,
      builder:
          (dialogContext) => StatefulBuilder(
            builder:
                (context, dialogSetState) => AlertDialog(
                  title: const Text('Aparência e leitura'),
                  content: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Modo escuro'),
                        subtitle: const Text(
                          'Reduz o brilho em ambientes escuros',
                        ),
                        value: darkMode,
                        onChanged: (value) {
                          dialogSetState(() => darkMode = value);
                          widget.onDarkModeChanged(value);
                        },
                      ),
                      const SizedBox(height: 8),
                      const Align(
                        alignment: Alignment.centerLeft,
                        child: Text(
                          'Tamanho do texto',
                          style: TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      Slider(
                        value: textScale,
                        min: .8,
                        max: 1.1,
                        divisions: 3,
                        label: '${(textScale * 100).round()}%',
                        onChanged: (value) {
                          dialogSetState(() => textScale = value);
                          widget.onTextScaleChanged(value);
                        },
                      ),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: const [Text('Menor'), Text('Maior')],
                      ),
                    ],
                  ),
                  actions: [
                    FilledButton(
                      onPressed: () => Navigator.pop(dialogContext),
                      child: const Text('Concluir'),
                    ),
                  ],
                ),
          ),
    );
  }
}

class _DashboardMetricCard extends StatelessWidget {
  const _DashboardMetricCard({
    required this.value,
    required this.label,
    required this.icon,
    required this.color,
    required this.onTap,
    this.helper,
    this.wide = false,
  });

  final int? value;
  final String label;
  final String? helper;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  final bool wide;

  @override
  Widget build(BuildContext context) {
    final isIOS = Theme.of(context).platform == TargetPlatform.iOS;
    return Material(
      color: Theme.of(context).colorScheme.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withValues(alpha: .18)),
          ),
          child: Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: .11),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      value == null || value! < 0 ? '—' : value.toString(),
                      style: TextStyle(
                        color: color,
                        fontSize: isIOS ? 18 : 20,
                        height: 1,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      label,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.onSurface,
                        fontSize: isIOS ? 10.2 : 11.5,
                        height: 1.12,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    if (helper != null)
                      Text(
                        helper!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: Color(0xFF748399),
                          fontSize: isIOS ? 8.5 : 9.5,
                        ),
                      ),
                  ],
                ),
              ),
              if (wide)
                Icon(Icons.chevron_right, color: color.withValues(alpha: .7)),
            ],
          ),
        ),
      ),
    );
  }
}

class AdmissionsHomePage extends StatelessWidget {
  const AdmissionsHomePage({
    super.key,
    required this.api,
    this.title = 'Auditorias',
    this.filter = '',
  });

  final MobileApi api;
  final String title;
  final String filter;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: AdmissionsPage(api: api, filter: filter),
    );
  }
}

class AdmissionsPage extends StatefulWidget {
  const AdmissionsPage({super.key, required this.api, this.filter = ''});

  final MobileApi api;
  final String filter;

  @override
  State<AdmissionsPage> createState() => _AdmissionsPageState();
}

class _AdmissionsPageState extends State<AdmissionsPage> {
  final _searchController = TextEditingController();
  List<AdmissionItem> _items = const [];
  List<_RecentAdmission> _recent = const [];
  Timer? _searchDebounce;
  bool _loading = true;
  String _hospitalFilter = '';
  String _insuranceFilter = '';
  String _statusFilter = '';
  int _periodDays = 0;
  String _sortBy = 'recent';

  @override
  void initState() {
    super.initState();
    _searchController.addListener(_onSearchChanged);
    _load();
    _loadRecents();
  }

  void _onSearchChanged() {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(
      const Duration(milliseconds: 400),
      () => _load(_searchController.text.trim()),
    );
  }

  Future<void> _loadRecents() async {
    final items = await _loadRecentAdmissions();
    if (mounted) setState(() => _recent = items);
  }

  List<AdmissionItem> get _visibleItems {
    final cutoff =
        _periodDays > 0
            ? DateTime.now().subtract(Duration(days: _periodDays))
            : null;
    final items =
        _items.where((item) {
          if (_hospitalFilter.isNotEmpty &&
              item.hospitalName != _hospitalFilter) {
            return false;
          }
          if (_insuranceFilter.isNotEmpty &&
              item.insuranceName != _insuranceFilter) {
            return false;
          }
          final pending = item.evolutionReport.trim().isEmpty;
          if (_statusFilter == 'pending' && !pending) return false;
          if (_statusFilter == 'visited' && pending) return false;
          if (cutoff != null) {
            final date = DateTime.tryParse(item.admissionDate);
            if (date == null || date.isBefore(cutoff)) return false;
          }
          return true;
        }).toList();

    switch (_sortBy) {
      case 'name':
        items.sort((a, b) => a.patientName.compareTo(b.patientName));
      case 'oldest':
        items.sort(
          (a, b) => _admissionDays(
            b.admissionDate,
          ).compareTo(_admissionDays(a.admissionDate)),
        );
      default:
        items.sort((a, b) => b.id.compareTo(a.id));
    }
    return items;
  }

  int get _activeFilterCount =>
      [
        _hospitalFilter,
        _insuranceFilter,
        _statusFilter,
        if (_periodDays > 0) 'period',
      ].where((item) => item.isNotEmpty).length;

  Future<void> _showFilters() async {
    var hospital = _hospitalFilter;
    var insurance = _insuranceFilter;
    var status = _statusFilter;
    var period = _periodDays;
    var sort = _sortBy;
    final hospitals =
        _items
            .map((item) => item.hospitalName)
            .where((item) => item.isNotEmpty)
            .toSet()
            .toList()
          ..sort();
    final insurers =
        _items
            .map((item) => item.insuranceName)
            .where((item) => item.isNotEmpty)
            .toSet()
            .toList()
          ..sort();

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder:
          (context) => StatefulBuilder(
            builder:
                (context, modalSetState) => SafeArea(
                  child: Padding(
                    padding: EdgeInsets.fromLTRB(
                      20,
                      18,
                      20,
                      20 + MediaQuery.viewInsetsOf(context).bottom,
                    ),
                    child: SingleChildScrollView(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Filtrar e ordenar',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 18),
                          DropdownButtonFormField<String>(
                            initialValue: hospital,
                            decoration: const InputDecoration(
                              labelText: 'Hospital',
                            ),
                            items: [
                              const DropdownMenuItem(
                                value: '',
                                child: Text('Todos'),
                              ),
                              ...hospitals.map(
                                (item) => DropdownMenuItem(
                                  value: item,
                                  child: Text(
                                    item,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ),
                            ],
                            onChanged:
                                (value) =>
                                    modalSetState(() => hospital = value ?? ''),
                          ),
                          const SizedBox(height: 12),
                          DropdownButtonFormField<String>(
                            initialValue: insurance,
                            decoration: const InputDecoration(
                              labelText: 'Convênio',
                            ),
                            items: [
                              const DropdownMenuItem(
                                value: '',
                                child: Text('Todos'),
                              ),
                              ...insurers.map(
                                (item) => DropdownMenuItem(
                                  value: item,
                                  child: Text(
                                    item,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ),
                            ],
                            onChanged:
                                (value) => modalSetState(
                                  () => insurance = value ?? '',
                                ),
                          ),
                          const SizedBox(height: 12),
                          DropdownButtonFormField<String>(
                            initialValue: status,
                            decoration: const InputDecoration(
                              labelText: 'Status da visita',
                            ),
                            items: const [
                              DropdownMenuItem(value: '', child: Text('Todos')),
                              DropdownMenuItem(
                                value: 'pending',
                                child: Text('Visita pendente'),
                              ),
                              DropdownMenuItem(
                                value: 'visited',
                                child: Text('Com visita'),
                              ),
                            ],
                            onChanged:
                                (value) =>
                                    modalSetState(() => status = value ?? ''),
                          ),
                          const SizedBox(height: 12),
                          DropdownButtonFormField<int>(
                            initialValue: period,
                            decoration: const InputDecoration(
                              labelText: 'Período da internação',
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 0,
                                child: Text('Qualquer período'),
                              ),
                              DropdownMenuItem(
                                value: 7,
                                child: Text('Últimos 7 dias'),
                              ),
                              DropdownMenuItem(
                                value: 30,
                                child: Text('Últimos 30 dias'),
                              ),
                              DropdownMenuItem(
                                value: 90,
                                child: Text('Últimos 90 dias'),
                              ),
                            ],
                            onChanged:
                                (value) =>
                                    modalSetState(() => period = value ?? 0),
                          ),
                          const SizedBox(height: 12),
                          DropdownButtonFormField<String>(
                            initialValue: sort,
                            decoration: const InputDecoration(
                              labelText: 'Ordenar por',
                            ),
                            items: const [
                              DropdownMenuItem(
                                value: 'recent',
                                child: Text('Data de internação'),
                              ),
                              DropdownMenuItem(
                                value: 'name',
                                child: Text('Nome'),
                              ),
                              DropdownMenuItem(
                                value: 'oldest',
                                child: Text('Maior permanência'),
                              ),
                            ],
                            onChanged:
                                (value) => modalSetState(
                                  () => sort = value ?? 'recent',
                                ),
                          ),
                          const SizedBox(height: 20),
                          Row(
                            children: [
                              TextButton(
                                onPressed: () {
                                  setState(() {
                                    _hospitalFilter = '';
                                    _insuranceFilter = '';
                                    _statusFilter = '';
                                    _periodDays = 0;
                                    _sortBy = 'recent';
                                  });
                                  Navigator.pop(context);
                                },
                                child: const Text('Limpar'),
                              ),
                              const Spacer(),
                              FilledButton(
                                onPressed: () {
                                  setState(() {
                                    _hospitalFilter = hospital;
                                    _insuranceFilter = insurance;
                                    _statusFilter = status;
                                    _periodDays = period;
                                    _sortBy = sort;
                                  });
                                  Navigator.pop(context);
                                },
                                child: const Text('Aplicar'),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
          ),
    );
  }

  Future<void> _load([String query = '']) async {
    setState(() => _loading = true);
    try {
      final items = await widget.api.listAdmissions(
        query,
        filter: widget.filter,
      );
      if (!mounted) return;
      setState(() => _items = items);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _searchController.removeListener(_onSearchChanged);
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () => _load(_searchController.text.trim()),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(
            controller: _searchController,
            textInputAction: TextInputAction.search,
            onSubmitted: _load,
            decoration: InputDecoration(
              hintText: 'Buscar beneficiário ou prestador',
              prefixIcon: const Icon(Icons.search, size: 20),
              suffixIcon: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_searchController.text.isNotEmpty)
                    IconButton(
                      tooltip: 'Limpar busca',
                      onPressed: _searchController.clear,
                      icon: const Icon(Icons.close, size: 19),
                    ),
                  Badge(
                    isLabelVisible: _activeFilterCount > 0,
                    label: Text('$_activeFilterCount'),
                    child: IconButton(
                      tooltip: 'Filtrar e ordenar',
                      onPressed: _showFilters,
                      icon: const Icon(Icons.tune, size: 21),
                    ),
                  ),
                ],
              ),
            ),
          ),
          if (_recent.isNotEmpty && _searchController.text.isEmpty) ...[
            const SizedBox(height: 14),
            const Text(
              'Acessados recentemente',
              style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            SizedBox(
              height: 72,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: _recent.length,
                separatorBuilder: (_, _) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final recent = _recent[index];
                  return ActionChip(
                    avatar: const Icon(Icons.history, size: 18),
                    label: SizedBox(
                      width: 132,
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            recent.patientName,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          if (recent.hospitalName.isNotEmpty)
                            Text(
                              recent.hospitalName,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                        ],
                      ),
                    ),
                    onPressed: () async {
                      await Navigator.of(context).push(
                        MaterialPageRoute(
                          builder:
                              (_) => AdmissionDetailPage(
                                api: widget.api,
                                admissionId: recent.id,
                              ),
                        ),
                      );
                      await _loadRecents();
                    },
                  );
                },
              ),
            ),
          ],
          const SizedBox(height: 12),
          if (!_loading)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Text(
                '${_visibleItems.length} paciente${_visibleItems.length == 1 ? '' : 's'}',
                style: const TextStyle(
                  color: Color(0xFF65758B),
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          if (_loading)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: CircularProgressIndicator(),
              ),
            )
          else
            ..._visibleItems.map(
              (item) => Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: () async {
                    await _rememberAdmission(item);
                    if (!context.mounted) return;
                    await Navigator.of(context).push(
                      MaterialPageRoute(
                        builder:
                            (_) => AdmissionDetailPage(
                              api: widget.api,
                              admissionId: item.id,
                            ),
                      ),
                    );
                    _load(_searchController.text.trim());
                  },
                  child: IntrinsicHeight(
                    child: Row(
                      children: [
                        Container(
                          width: 5,
                          decoration: const BoxDecoration(
                            color: Color(0xFF2D63A6),
                            borderRadius: BorderRadius.only(
                              topLeft: Radius.circular(12),
                              bottomLeft: Radius.circular(12),
                            ),
                          ),
                        ),
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(12, 10, 8, 10),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Expanded(
                                      child: Text(
                                        item.patientName,
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.w800,
                                          color:
                                              Theme.of(
                                                context,
                                              ).colorScheme.onSurface,
                                        ),
                                      ),
                                    ),
                                    _CaseBadge(
                                      label:
                                          '${_admissionDays(item.admissionDate)}d',
                                      backgroundColor:
                                          _admissionDays(item.admissionDate) >=
                                                  30
                                              ? const Color(0xFFFFE8D5)
                                              : const Color(0xFFE5F0FA),
                                      textColor:
                                          _admissionDays(item.admissionDate) >=
                                                  30
                                              ? const Color(0xFF9A3412)
                                              : const Color(0xFF235B91),
                                    ),
                                    if (item.evolutionReport.trim().isEmpty)
                                      const Padding(
                                        padding: EdgeInsets.only(left: 5),
                                        child: Tooltip(
                                          message: 'Visita pendente',
                                          child: Icon(
                                            Icons.notification_important,
                                            size: 18,
                                            color: Color(0xFFB54708),
                                          ),
                                        ),
                                      ),
                                  ],
                                ),
                                const SizedBox(height: 5),
                                _CompactInfoLine(
                                  icon: Icons.local_hospital_outlined,
                                  text:
                                      item.hospitalName.isEmpty
                                          ? 'Prestador não informado'
                                          : item.hospitalName,
                                ),
                                const SizedBox(height: 3),
                                _CompactInfoLine(
                                  icon: Icons.health_and_safety_outlined,
                                  text:
                                      item.insuranceName.isEmpty
                                          ? 'Convênio não informado'
                                          : item.insuranceName,
                                ),
                                if (item.cidCode.trim().isNotEmpty) ...[
                                  const SizedBox(height: 3),
                                  _CompactInfoLine(
                                    icon: Icons.badge_outlined,
                                    text: 'CID ${item.cidCode}',
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ),
                        const Padding(
                          padding: EdgeInsets.only(right: 8),
                          child: Icon(
                            Icons.chevron_right,
                            color: Color(0xFF2D63A6),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class HomeCareCasesPage extends StatefulWidget {
  const HomeCareCasesPage({
    super.key,
    required this.api,
    this.initialQuery = '',
  });

  final MobileApi api;
  final String initialQuery;

  @override
  State<HomeCareCasesPage> createState() => _HomeCareCasesPageState();
}

class _HomeCareCasesPageState extends State<HomeCareCasesPage> {
  static const List<String> _statusOptions = [
    'em_avaliacao',
    'elegivel',
    'implantacao',
    'aguardando_familia',
    'aguardando_hospital',
    'aguardando_operadora',
    'implantado',
    'negado',
    'descontinuado',
  ];

  static const List<String> _modeOptions = [
    'procedimento_pontual',
    'atendimento_multiprofissional',
    'internacao_domiciliar_6h',
    'internacao_domiciliar_12h',
    'internacao_domiciliar_24h',
  ];

  static const List<String> _barrierOptions = [
    'familia',
    'ambiente',
    'fornecedor',
    'hospital',
    'operadora',
    'equipamentos',
    'clinica',
    'outros',
  ];

  late final TextEditingController _searchController;
  List<HomeCareCase> _items = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController(text: widget.initialQuery);
    _load(widget.initialQuery);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _labelize(String value) {
    if (value.trim().isEmpty) return '-';
    if (value == 'hospital') return 'Prestador';
    return value
        .split('_')
        .map((part) {
          if (part == 'hospital') return 'Prestador';
          if (part.isEmpty) return part;
          return '${part[0].toUpperCase()}${part.substring(1)}';
        })
        .join(' ');
  }

  String _formatDate(String value) {
    final raw = value.trim();
    if (raw.isEmpty) return '-';
    final datePart = raw.split(' ').first;
    final parts = datePart.split('-');
    if (parts.length != 3) return raw;
    return '${parts[2]}/${parts[1]}/${parts[0]}';
  }

  bool _isHomeCareCase(HomeCareCase item) {
    return item.updateId > 0 ||
        item.flaggedHomeCare.trim().toLowerCase() == 's' ||
        item.status.trim().isNotEmpty ||
        item.neadEligible.trim().toLowerCase() == 's';
  }

  Future<void> _load([String query = '']) async {
    setState(() => _loading = true);
    try {
      final items = await widget.api.listHomeCareCases(query);
      if (!mounted) return;
      setState(() => _items = items.where(_isHomeCareCase).toList());
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _openUpdate(HomeCareCase item) async {
    final statusController = TextEditingController(text: item.status);
    final supplierController = TextEditingController(text: item.supplier);
    final modeController = TextEditingController(text: item.approvedMode);
    final expectedDateController = TextEditingController(
      text: item.expectedDate.isEmpty ? '' : _formatDate(item.expectedDate),
    );
    final barrierController = TextEditingController(text: item.mainBarrier);
    final transitionController = TextEditingController(
      text: item.transitionPlan,
    );
    final notesController = TextEditingController(text: item.notes);
    bool saved = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder:
          (context) => StatefulBuilder(
            builder:
                (context, setModalState) => Padding(
                  padding: EdgeInsets.only(
                    left: 16,
                    right: 16,
                    top: 16,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 16,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.patientName,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Atualização de Home Care',
                          style: TextStyle(color: Colors.blueGrey.shade700),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue:
                              statusController.text.trim().isEmpty
                                  ? null
                                  : statusController.text.trim(),
                          items:
                              _statusOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              statusController.text = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Status',
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue:
                              modeController.text.trim().isEmpty
                                  ? null
                                  : modeController.text.trim(),
                          items:
                              _modeOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              modeController.text = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Modalidade aprovada',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: supplierController,
                          decoration: const InputDecoration(
                            labelText: 'Fornecedor',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: expectedDateController,
                          readOnly: true,
                          onTap: () async {
                            final now = DateTime.now();
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: now,
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2100),
                            );
                            if (picked == null) return;
                            setModalState(() {
                              expectedDateController.text =
                                  '${picked.day.toString().padLeft(2, '0')}/${picked.month.toString().padLeft(2, '0')}/${picked.year.toString().padLeft(4, '0')}';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Previsão de implantação',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue:
                              barrierController.text.trim().isEmpty
                                  ? null
                                  : barrierController.text.trim(),
                          items:
                              _barrierOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              barrierController.text = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Barreira principal',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: transitionController,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            labelText: 'Plano de transição',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: notesController,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            labelText: 'Observações',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () async {
                            final expectedDate =
                                expectedDateController.text.trim();
                            String apiDate = '';
                            if (expectedDate.isNotEmpty) {
                              final parts = expectedDate.split('/');
                              if (parts.length == 3) {
                                apiDate = '${parts[2]}-${parts[1]}-${parts[0]}';
                              }
                            }

                            await widget.api.saveHomeCareUpdate(
                              admissionId: item.admissionId,
                              status: statusController.text.trim(),
                              supplier: supplierController.text.trim(),
                              approvedMode: modeController.text.trim(),
                              expectedDate: apiDate,
                              mainBarrier: barrierController.text.trim(),
                              transitionPlan: transitionController.text.trim(),
                              notes: notesController.text.trim(),
                            );
                            saved = true;
                            if (!context.mounted) return;
                            Navigator.of(context).pop();
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(50),
                            backgroundColor: const Color(0xFF0F766E),
                          ),
                          child: const Text('Salvar atualização'),
                        ),
                      ],
                    ),
                  ),
                ),
          ),
    );

    if (saved) {
      await _load(_searchController.text.trim());
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Atualização de Home Care salva com sucesso.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final withStatus = _items.where((item) => item.status.trim().isNotEmpty);
    final eligible = _items.where(
      (item) => item.neadEligible.trim().toLowerCase() == 's',
    );

    return Scaffold(
      appBar: AppBar(title: const Text('Home Care')),
      body: RefreshIndicator(
        onRefresh: () => _load(_searchController.text.trim()),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextField(
              controller: _searchController,
              textInputAction: TextInputAction.search,
              onSubmitted: _load,
              decoration: InputDecoration(
                labelText: 'Pesquisar por beneficiário, prestador ou convênio',
                suffixIcon: IconButton(
                  onPressed: () => _load(_searchController.text.trim()),
                  icon: const Icon(Icons.search),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Casos HC',
                    value: '${_items.length}',
                    accentColor: const Color(0xFF0F766E),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Com status',
                    value: '${withStatus.length}',
                    accentColor: const Color(0xFF2D63A6),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Elegíveis',
                    value: '${eligible.length}',
                    accentColor: const Color(0xFF5E2363),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (_loading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (_items.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text('Nenhum caso encontrado para Home Care.'),
                ),
              )
            else
              ..._items.map(
                (item) => Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.patientName,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    item.hospitalName.isEmpty
                                        ? '-'
                                        : item.hospitalName,
                                  ),
                                  Text(
                                    item.insuranceName.isEmpty
                                        ? '-'
                                        : item.insuranceName,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                _CaseBadge(
                                  label: '${item.days}d',
                                  backgroundColor: const Color(0xFFEEF4FB),
                                  textColor: const Color(0xFF2D63A6),
                                ),
                                const SizedBox(height: 6),
                                _CaseBadge(
                                  label:
                                      item.status.trim().isEmpty
                                          ? 'Sem status'
                                          : _labelize(item.status),
                                  backgroundColor: const Color(0xFFECFDF5),
                                  textColor: const Color(0xFF0F766E),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            if (item.neadClassification.trim().isNotEmpty)
                              _CaseBadge(
                                label: item.neadClassification,
                                backgroundColor: const Color(0xFFF6F0FB),
                                textColor: const Color(0xFF5E2363),
                              ),
                            if (item.flaggedHomeCare.trim().toLowerCase() ==
                                's')
                              const _CaseBadge(
                                label: 'Sinalizado',
                                backgroundColor: Color(0xFFFFF8EC),
                                textColor: Color(0xFF8B5E1A),
                              ),
                            if (item.approvedMode.trim().isNotEmpty)
                              _CaseBadge(
                                label: _labelize(item.approvedMode),
                                backgroundColor: const Color(0xFFEEF4FB),
                                textColor: const Color(0xFF2D63A6),
                              )
                            else if (item.suggestedMode.trim().isNotEmpty)
                              _CaseBadge(
                                label:
                                    'Sugestão: ${_labelize(item.suggestedMode)}',
                                backgroundColor: const Color(0xFFEEF4FB),
                                textColor: const Color(0xFF2D63A6),
                              ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        if (item.mainBarrier.trim().isNotEmpty)
                          Text(
                            'Barreira: ${_labelize(item.mainBarrier)}',
                            style: const TextStyle(
                              color: Color(0xFF5B6577),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        if (item.expectedDate.trim().isNotEmpty)
                          Text(
                            'Implantação prevista: ${_formatDate(item.expectedDate)}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        if (item.supplier.trim().isNotEmpty)
                          Text(
                            'Fornecedor: ${item.supplier}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        if (item.updatedAt.trim().isNotEmpty)
                          Text(
                            'Última atualização: ${_formatDate(item.updatedAt)}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: () => _openUpdate(item),
                            style: FilledButton.styleFrom(
                              backgroundColor: const Color(0xFF0F766E),
                            ),
                            child: Text(
                              item.updateId > 0
                                  ? 'Lançar nova atualização'
                                  : 'Iniciar atualização',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class LongStayCasesPage extends StatefulWidget {
  const LongStayCasesPage({
    super.key,
    required this.api,
    this.initialQuery = '',
  });

  final MobileApi api;
  final String initialQuery;

  @override
  State<LongStayCasesPage> createState() => _LongStayCasesPageState();
}

class _LongStayCasesPageState extends State<LongStayCasesPage> {
  late final TextEditingController _searchController;
  List<LongStayCase> _items = const [];
  List<String> _statusOptions = const [];
  List<String> _reasonOptions = const [];
  List<String> _riskOptions = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController(text: widget.initialQuery);
    _load(widget.initialQuery);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _labelize(String value) {
    if (value.trim().isEmpty) return '-';
    if (value == 'hospital') return 'Prestador';
    return value
        .split('_')
        .map((part) {
          if (part == 'hospital') return 'Prestador';
          if (part.isEmpty) return part;
          return '${part[0].toUpperCase()}${part.substring(1)}';
        })
        .join(' ');
  }

  String _formatDate(String value) {
    final raw = value.trim();
    if (raw.isEmpty) return '-';
    final datePart = raw.split(' ').first;
    final parts = datePart.split('-');
    if (parts.length != 3) return raw;
    return '${parts[2]}/${parts[1]}/${parts[0]}';
  }

  Future<void> _load([String query = '']) async {
    setState(() => _loading = true);
    try {
      final results = await Future.wait([
        widget.api.listLongStayCases(query),
        widget.api.listLongStayStatuses(),
        widget.api.listLongStayReasons(),
        widget.api.listLongStayRisks(),
      ]);
      if (!mounted) return;
      setState(() {
        _items = results[0] as List<LongStayCase>;
        _statusOptions = results[1] as List<String>;
        _reasonOptions = results[2] as List<String>;
        _riskOptions = results[3] as List<String>;
      });
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _openUpdate(LongStayCase item) async {
    final ownerController = TextEditingController(text: item.owner);
    final clinicalBarrierController = TextEditingController(
      text: item.clinicalBarrier,
    );
    final administrativeBarrierController = TextEditingController(
      text: item.administrativeBarrier,
    );
    final actionPlanController = TextEditingController(text: item.actionPlan);
    final notesController = TextEditingController(text: item.notes);
    final deadlineController = TextEditingController();
    final nextReviewController = TextEditingController(
      text: item.nextReviewDate.isEmpty ? '' : _formatDate(item.nextReviewDate),
    );
    final expectedDischargeController = TextEditingController(
      text:
          item.expectedDischargeDate.isEmpty
              ? ''
              : _formatDate(item.expectedDischargeDate),
    );
    String selectedStatus = item.status.trim();
    String selectedReason = item.mainReason.trim();
    String selectedRisk = item.riskLevel.trim();
    bool escalated = item.escalatedFlag.trim().toLowerCase() == 's';
    bool dehospitalization =
        item.dehospitalizationFlag.trim().toLowerCase() == 's';
    bool saved = false;

    Future<void> pickDate(TextEditingController controller) async {
      final picked = await showDatePicker(
        context: context,
        initialDate: DateTime.now(),
        firstDate: DateTime(2020),
        lastDate: DateTime(2100),
      );
      if (picked == null) return;
      controller.text =
          '${picked.day.toString().padLeft(2, '0')}/${picked.month.toString().padLeft(2, '0')}/${picked.year.toString().padLeft(4, '0')}';
    }

    String toApiDate(String displayValue) {
      final parts = displayValue.trim().split('/');
      if (parts.length != 3) return '';
      return '${parts[2]}-${parts[1]}-${parts[0]}';
    }

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder:
          (context) => StatefulBuilder(
            builder:
                (context, setModalState) => Padding(
                  padding: EdgeInsets.only(
                    left: 16,
                    right: 16,
                    top: 16,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 16,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.patientName,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Atualização de longa permanência',
                          style: TextStyle(color: Colors.blueGrey.shade700),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue:
                              selectedStatus.isEmpty ? null : selectedStatus,
                          items:
                              _statusOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              selectedStatus = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Status atual',
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue:
                              selectedReason.isEmpty ? null : selectedReason,
                          items:
                              _reasonOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              selectedReason = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Motivo principal',
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue:
                              selectedRisk.isEmpty ? null : selectedRisk,
                          items:
                              _riskOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              selectedRisk = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Risco sinistro',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: ownerController,
                          decoration: const InputDecoration(
                            labelText: 'Responsável',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: nextReviewController,
                          readOnly: true,
                          onTap: () => pickDate(nextReviewController),
                          decoration: const InputDecoration(
                            labelText: 'Próxima revisão',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: expectedDischargeController,
                          readOnly: true,
                          onTap: () => pickDate(expectedDischargeController),
                          decoration: const InputDecoration(
                            labelText: 'Previsão de alta',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: deadlineController,
                          readOnly: true,
                          onTap: () => pickDate(deadlineController),
                          decoration: const InputDecoration(
                            labelText: 'Prazo da ação',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        SwitchListTile(
                          value: escalated,
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Necessita escalonamento'),
                          onChanged: (value) {
                            setModalState(() {
                              escalated = value;
                            });
                          },
                        ),
                        SwitchListTile(
                          value: dehospitalization,
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Potencial de desospitalização'),
                          onChanged: (value) {
                            setModalState(() {
                              dehospitalization = value;
                            });
                          },
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: clinicalBarrierController,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            labelText: 'Barreira clínica',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: administrativeBarrierController,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            labelText: 'Barreira administrativa',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: actionPlanController,
                          minLines: 3,
                          maxLines: 6,
                          decoration: const InputDecoration(
                            labelText: 'Plano de ação',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: notesController,
                          minLines: 3,
                          maxLines: 5,
                          decoration: const InputDecoration(
                            labelText: 'Observações',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () async {
                            await widget.api.saveLongStayUpdate(
                              admissionId: item.admissionId,
                              status: selectedStatus,
                              mainReason: selectedReason,
                              clinicalBarrier:
                                  clinicalBarrierController.text.trim(),
                              administrativeBarrier:
                                  administrativeBarrierController.text.trim(),
                              actionPlan: actionPlanController.text.trim(),
                              owner: ownerController.text.trim(),
                              deadlineDate: toApiDate(deadlineController.text),
                              expectedDischargeDate: toApiDate(
                                expectedDischargeController.text,
                              ),
                              nextReviewDate: toApiDate(
                                nextReviewController.text,
                              ),
                              dehospitalizationFlag:
                                  dehospitalization ? 's' : 'n',
                              escalatedFlag: escalated ? 's' : 'n',
                              riskLevel: selectedRisk,
                              notes: notesController.text.trim(),
                            );
                            saved = true;
                            if (!context.mounted) return;
                            Navigator.of(context).pop();
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(50),
                            backgroundColor: const Color(0xFF5E2363),
                          ),
                          child: const Text('Salvar atualização'),
                        ),
                      ],
                    ),
                  ),
                ),
          ),
    );

    if (saved) {
      await _load(_searchController.text.trim());
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Atualização de longa permanência salva com sucesso.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final escalated = _items.where(
      (item) => item.escalatedFlag.trim().toLowerCase() == 's',
    );
    final withoutStatus = _items.where((item) => item.status.trim().isEmpty);

    return Scaffold(
      appBar: AppBar(title: const Text('Longa permanência')),
      body: RefreshIndicator(
        onRefresh: () => _load(_searchController.text.trim()),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextField(
              controller: _searchController,
              textInputAction: TextInputAction.search,
              onSubmitted: _load,
              decoration: InputDecoration(
                labelText:
                    'Pesquisar por beneficiário, prestador, convênio ou status',
                suffixIcon: IconButton(
                  onPressed: () => _load(_searchController.text.trim()),
                  icon: const Icon(Icons.search),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Casos',
                    value: '${_items.length}',
                    accentColor: const Color(0xFF5E2363),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Escalonados',
                    value: '${escalated.length}',
                    accentColor: const Color(0xFF8B5E1A),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Sem status',
                    value: '${withoutStatus.length}',
                    accentColor: const Color(0xFF2D63A6),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (_loading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (_items.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text('Nenhum caso encontrado para longa permanência.'),
                ),
              )
            else
              ..._items.map(
                (item) => Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.patientName,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    item.hospitalName.isEmpty
                                        ? '-'
                                        : item.hospitalName,
                                  ),
                                  Text(
                                    item.insuranceName.isEmpty
                                        ? '-'
                                        : item.insuranceName,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                _CaseBadge(
                                  label:
                                      '${item.days}d / limiar ${item.thresholdDays}d',
                                  backgroundColor: const Color(0xFFEEF4FB),
                                  textColor: const Color(0xFF2D63A6),
                                ),
                                const SizedBox(height: 6),
                                _CaseBadge(
                                  label:
                                      item.status.trim().isEmpty
                                          ? 'Sem status'
                                          : _labelize(item.status),
                                  backgroundColor: const Color(0xFFF6F0FB),
                                  textColor: const Color(0xFF5E2363),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            if (item.mainReason.trim().isNotEmpty)
                              _CaseBadge(
                                label: _labelize(item.mainReason),
                                backgroundColor: const Color(0xFFFFF8EC),
                                textColor: const Color(0xFF8B5E1A),
                              ),
                            if (item.riskLevel.trim().isNotEmpty)
                              _CaseBadge(
                                label: 'Risco ${_labelize(item.riskLevel)}',
                                backgroundColor: const Color(0xFFECFDF5),
                                textColor: const Color(0xFF0F766E),
                              ),
                            if (item.escalatedFlag.trim().toLowerCase() == 's')
                              const _CaseBadge(
                                label: 'Escalonado',
                                backgroundColor: Color(0xFFFDECEC),
                                textColor: Color(0xFFC2410C),
                              ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        if (item.nextReviewDate.trim().isNotEmpty)
                          Text(
                            'Próxima revisão: ${_formatDate(item.nextReviewDate)}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        if (item.expectedDischargeDate.trim().isNotEmpty)
                          Text(
                            'Previsão de alta: ${_formatDate(item.expectedDischargeDate)}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        if (item.owner.trim().isNotEmpty)
                          Text(
                            'Responsável: ${item.owner}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        if (item.updatedAt.trim().isNotEmpty)
                          Text(
                            'Última atualização: ${_formatDate(item.updatedAt)}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: () => _openUpdate(item),
                            style: FilledButton.styleFrom(
                              backgroundColor: const Color(0xFF5E2363),
                            ),
                            child: Text(
                              item.updateId > 0
                                  ? 'Lançar nova atualização'
                                  : 'Iniciar atualização',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class AdverseEventCasesPage extends StatefulWidget {
  const AdverseEventCasesPage({
    super.key,
    required this.api,
    this.initialQuery = '',
  });

  final MobileApi api;
  final String initialQuery;

  @override
  State<AdverseEventCasesPage> createState() => _AdverseEventCasesPageState();
}

class _AdverseEventCasesPageState extends State<AdverseEventCasesPage> {
  late final TextEditingController _searchController;
  List<AdverseEventCase> _items = const [];
  List<String> _eventTypes = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _searchController = TextEditingController(text: widget.initialQuery);
    _load(widget.initialQuery);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  String _formatDate(String value) {
    final raw = value.trim();
    if (raw.isEmpty) return '-';
    final datePart = raw.split(' ').first;
    final parts = datePart.split('-');
    if (parts.length != 3) return raw;
    return '${parts[2]}/${parts[1]}/${parts[0]}';
  }

  bool _hasAdverseEvent(AdverseEventCase item) {
    return item.updateId > 0 ||
        item.signaledFlag.trim().toLowerCase() == 's' ||
        item.eventType.trim().isNotEmpty ||
        item.eventDate.trim().isNotEmpty ||
        item.report.trim().isNotEmpty;
  }

  Future<void> _load([String query = '']) async {
    setState(() => _loading = true);
    try {
      final results = await Future.wait([
        widget.api.listAdverseEventCases(query),
        widget.api.listAdverseEventTypes(),
      ]);
      if (!mounted) return;
      setState(() {
        _items = results[0] as List<AdverseEventCase>;
        _eventTypes = results[1] as List<String>;
      });
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _openUpdate(AdverseEventCase item) async {
    final reportController = TextEditingController(text: item.report);
    final dateController = TextEditingController(
      text: item.eventDate.isEmpty ? '' : _formatDate(item.eventDate),
    );
    String selectedType =
        item.eventType.trim().isNotEmpty
            ? item.eventType.trim()
            : (_eventTypes.isNotEmpty ? _eventTypes.first : '');
    bool signaled = item.signaledFlag.trim().toLowerCase() != 'n';
    bool concluded = item.concludedFlag.trim().toLowerCase() == 's';
    bool closed = item.closeFlag.trim().toLowerCase() == 's';
    bool saved = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder:
          (context) => StatefulBuilder(
            builder:
                (context, setModalState) => Padding(
                  padding: EdgeInsets.only(
                    left: 16,
                    right: 16,
                    top: 16,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 16,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item.patientName,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Lançamento de evento adverso',
                          style: TextStyle(color: Colors.blueGrey.shade700),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue:
                              selectedType.isEmpty ? null : selectedType,
                          items:
                              _eventTypes
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(item),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              selectedType = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Tipo do evento',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: dateController,
                          readOnly: true,
                          onTap: () async {
                            final picked = await showDatePicker(
                              context: context,
                              initialDate: DateTime.now(),
                              firstDate: DateTime(2020),
                              lastDate: DateTime(2100),
                            );
                            if (picked == null) return;
                            setModalState(() {
                              dateController.text =
                                  '${picked.day.toString().padLeft(2, '0')}/${picked.month.toString().padLeft(2, '0')}/${picked.year.toString().padLeft(4, '0')}';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Data do evento',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        SwitchListTile(
                          value: signaled,
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Evento sinalizado'),
                          onChanged: (value) {
                            setModalState(() {
                              signaled = value;
                            });
                          },
                        ),
                        SwitchListTile(
                          value: concluded,
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Evento concluído'),
                          onChanged: (value) {
                            setModalState(() {
                              concluded = value;
                            });
                          },
                        ),
                        SwitchListTile(
                          value: closed,
                          contentPadding: EdgeInsets.zero,
                          title: const Text('Encerrar evento'),
                          onChanged: (value) {
                            setModalState(() {
                              closed = value;
                            });
                          },
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: reportController,
                          minLines: 4,
                          maxLines: 7,
                          maxLength: 4000,
                          decoration: const InputDecoration(
                            labelText: 'Relato / atualização',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () async {
                            if (concluded || closed) {
                              final confirmed = await showDialog<bool>(
                                context: context,
                                builder:
                                    (dialogContext) => AlertDialog(
                                      icon: const Icon(
                                        Icons.warning_amber_rounded,
                                        color: Color(0xFFB54708),
                                      ),
                                      title: Text(
                                        closed
                                            ? 'Encerrar este evento?'
                                            : 'Concluir este evento?',
                                      ),
                                      content: const Text(
                                        'Confirme somente após revisar o relato. Essa mudança altera o status operacional do evento.',
                                      ),
                                      actions: [
                                        TextButton(
                                          onPressed:
                                              () => Navigator.pop(
                                                dialogContext,
                                                false,
                                              ),
                                          child: const Text('Revisar'),
                                        ),
                                        FilledButton(
                                          onPressed:
                                              () => Navigator.pop(
                                                dialogContext,
                                                true,
                                              ),
                                          child: const Text('Confirmar'),
                                        ),
                                      ],
                                    ),
                              );
                              if (confirmed != true) return;
                            }
                            final parts = dateController.text.trim().split('/');
                            final eventDate =
                                parts.length == 3
                                    ? '${parts[2]}-${parts[1]}-${parts[0]}'
                                    : '';
                            await widget.api.saveAdverseEventUpdate(
                              admissionId: item.admissionId,
                              eventType: selectedType,
                              report: reportController.text.trim(),
                              eventDate: eventDate,
                              signaledFlag: signaled ? 's' : 'n',
                              concludedFlag: concluded ? 's' : 'n',
                              closeFlag: closed ? 's' : 'n',
                            );
                            saved = true;
                            if (!context.mounted) return;
                            Navigator.of(context).pop();
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(50),
                            backgroundColor: const Color(0xFF8B5E1A),
                          ),
                          child: const Text('Salvar atualização'),
                        ),
                      ],
                    ),
                  ),
                ),
          ),
    );

    if (saved) {
      await _load(_searchController.text.trim());
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Atualização de evento adverso salva com sucesso.'),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final eventItems = _items.where(_hasAdverseEvent).toList();
    final open = eventItems.where(
      (item) => item.closeFlag.trim().toLowerCase() != 's',
    );
    final concluded = eventItems.where(
      (item) => item.concludedFlag.trim().toLowerCase() == 's',
    );

    return Scaffold(
      appBar: AppBar(title: const Text('Evento adverso')),
      body: RefreshIndicator(
        onRefresh: () => _load(_searchController.text.trim()),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TextField(
              controller: _searchController,
              textInputAction: TextInputAction.search,
              onSubmitted: _load,
              decoration: InputDecoration(
                labelText:
                    'Pesquisar por beneficiário, prestador, convênio ou tipo',
                suffixIcon: IconButton(
                  onPressed: () => _load(_searchController.text.trim()),
                  icon: const Icon(Icons.search),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Eventos',
                    value: '${eventItems.length}',
                    accentColor: const Color(0xFF8B5E1A),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Em aberto',
                    value: '${open.length}',
                    accentColor: const Color(0xFFC2410C),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: _InfoMetricCard(
                    label: 'Concluídos',
                    value: '${concluded.length}',
                    accentColor: const Color(0xFF2D63A6),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            if (_loading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (_items.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text('Nenhum caso encontrado para evento adverso.'),
                ),
              )
            else if (eventItems.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text('Nenhum evento adverso registrado.'),
                ),
              )
            else
              ...eventItems.map(
                (item) => Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    item.patientName,
                                    style: const TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    item.hospitalName.isEmpty
                                        ? '-'
                                        : item.hospitalName,
                                  ),
                                  Text(
                                    item.insuranceName.isEmpty
                                        ? '-'
                                        : item.insuranceName,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                _CaseBadge(
                                  label: '${item.days}d',
                                  backgroundColor: const Color(0xFFEEF4FB),
                                  textColor: const Color(0xFF2D63A6),
                                ),
                                const SizedBox(height: 6),
                                _CaseBadge(
                                  label:
                                      item.eventType.trim().isEmpty
                                          ? 'Sem evento'
                                          : item.eventType,
                                  backgroundColor: const Color(0xFFFFF8EC),
                                  textColor: const Color(0xFF8B5E1A),
                                ),
                              ],
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _CaseBadge(
                              label:
                                  item.signaledFlag.trim().toLowerCase() == 's'
                                      ? 'Sinalizado'
                                      : 'Não sinalizado',
                              backgroundColor: const Color(0xFFF6F0FB),
                              textColor: const Color(0xFF5E2363),
                            ),
                            if (item.concludedFlag.trim().toLowerCase() == 's')
                              const _CaseBadge(
                                label: 'Concluído',
                                backgroundColor: Color(0xFFECFDF5),
                                textColor: Color(0xFF0F766E),
                              ),
                            if (item.closeFlag.trim().toLowerCase() == 's')
                              const _CaseBadge(
                                label: 'Encerrado',
                                backgroundColor: Color(0xFFEEF4FB),
                                textColor: Color(0xFF2D63A6),
                              ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        if (item.eventDate.trim().isNotEmpty)
                          Text(
                            'Data do evento: ${_formatDate(item.eventDate)}',
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        if (item.report.trim().isNotEmpty)
                          Text(
                            item.report,
                            maxLines: 3,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(color: Color(0xFF5B6577)),
                          ),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: FilledButton(
                            onPressed: () => _openUpdate(item),
                            style: FilledButton.styleFrom(
                              backgroundColor: const Color(0xFF8B5E1A),
                            ),
                            child: Text(
                              item.updateId > 0
                                  ? 'Lançar nova atualização'
                                  : 'Lançar evento',
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class ModuleOverviewEntry {
  const ModuleOverviewEntry({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  final IconData icon;
  final String title;
  final String subtitle;
}

class ModuleOverviewPage extends StatelessWidget {
  const ModuleOverviewPage({
    super.key,
    required this.api,
    required this.title,
    required this.description,
    required this.icon,
    required this.accentColor,
    required this.entries,
  });

  final MobileApi api;
  final String title;
  final String description;
  final IconData icon;
  final Color accentColor;
  final List<ModuleOverviewEntry> entries;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 54,
                    height: 54,
                    decoration: BoxDecoration(
                      color: accentColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: Icon(icon, color: accentColor, size: 30),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    title,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1D2940),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    description,
                    style: const TextStyle(
                      fontSize: 14,
                      color: Color(0xFF5B6577),
                      height: 1.45,
                    ),
                  ),
                  const SizedBox(height: 16),
                  SizedBox(
                    width: double.infinity,
                    child: FilledButton.icon(
                      onPressed:
                          () => Navigator.of(context).push(
                            MaterialPageRoute(
                              builder:
                                  (_) => AdmissionsHomePage(
                                    api: api,
                                    title: 'Registros operacionais',
                                  ),
                            ),
                          ),
                      icon: const Icon(Icons.list_alt_outlined),
                      label: const Text('Abrir registros operacionais'),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 12),
          ...entries.map(
            (entry) => Card(
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                leading: Icon(entry.icon, color: accentColor),
                title: Text(entry.title),
                subtitle: Text(entry.subtitle),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class AdmissionDetailPage extends StatefulWidget {
  const AdmissionDetailPage({
    super.key,
    required this.api,
    required this.admissionId,
  });

  final MobileApi api;
  final int admissionId;

  @override
  State<AdmissionDetailPage> createState() => _AdmissionDetailPageState();
}

class _AdmissionTimelineEntry {
  const _AdmissionTimelineEntry({
    required this.date,
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.color,
  });

  final DateTime date;
  final String title;
  final String subtitle;
  final IconData icon;
  final Color color;
}

class _AdmissionDetailPageState extends State<AdmissionDetailPage> {
  static const List<String> _homeCareStatusOptions = [
    'em_avaliacao',
    'elegivel',
    'implantacao',
    'aguardando_familia',
    'aguardando_hospital',
    'aguardando_operadora',
    'implantado',
    'negado',
    'descontinuado',
  ];

  static const List<String> _homeCareModeOptions = [
    'procedimento_pontual',
    'atendimento_multiprofissional',
    'internacao_domiciliar_6h',
    'internacao_domiciliar_12h',
    'internacao_domiciliar_24h',
  ];

  static const List<String> _homeCareBarrierOptions = [
    'familia',
    'ambiente',
    'fornecedor',
    'hospital',
    'operadora',
    'equipamentos',
    'clinica',
    'outros',
  ];

  AdmissionDetail? _detail;
  bool _loading = true;

  DateTime _timelineDate(String value) =>
      DateTime.tryParse(value.trim()) ?? DateTime(1900);

  List<_AdmissionTimelineEntry> _timeline(AdmissionDetail detail) {
    final entries = <_AdmissionTimelineEntry>[
      ...detail.evolutions.map(
        (item) => _AdmissionTimelineEntry(
          date: _timelineDate(item.visitedAt),
          title: 'Visita ${item.visitNumber > 0 ? '#${item.visitNumber}' : ''}',
          subtitle:
              item.report.trim().isEmpty ? 'Visita registrada' : item.report,
          icon: Icons.medical_information_outlined,
          color: const Color(0xFF2D63A6),
        ),
      ),
      ...detail.extensions.map(
        (item) => _AdmissionTimelineEntry(
          date: _timelineDate(item.startDate),
          title: 'Prorrogação de ${item.days} diária(s)',
          subtitle:
              '${_formatDate(item.startDate)} a ${_formatDate(item.endDate)}',
          icon: Icons.event_repeat,
          color: const Color(0xFF5E2363),
        ),
      ),
      ...detail.tussItems.map(
        (item) => _AdmissionTimelineEntry(
          date: _timelineDate(
            item.performedAt.isNotEmpty ? item.performedAt : item.releasedAt,
          ),
          title: 'TUSS ${item.code}',
          subtitle: item.description,
          icon: Icons.playlist_add_check_circle_outlined,
          color: const Color(0xFF0F766E),
        ),
      ),
      ...detail.adverseEvents.map(
        (item) => _AdmissionTimelineEntry(
          date: _timelineDate(item.eventDate),
          title: 'Evento adverso',
          subtitle:
              item.eventType.trim().isEmpty
                  ? 'Evento sinalizado'
                  : _labelize(item.eventType),
          icon: Icons.warning_amber_rounded,
          color: const Color(0xFFB54708),
        ),
      ),
    ];
    if (detail.admission.dischargeDate.isNotEmpty) {
      entries.add(
        _AdmissionTimelineEntry(
          date: _timelineDate(detail.admission.dischargeDate),
          title: 'Alta registrada',
          subtitle: detail.admission.dischargeType,
          icon: Icons.logout,
          color: const Color(0xFF0F766E),
        ),
      );
    }
    entries.sort((a, b) => b.date.compareTo(a.date));
    return entries;
  }

  String _nextAction(AdmissionDetail detail) {
    if (detail.admission.dischargeDate.isNotEmpty) {
      return 'Internação encerrada';
    }
    final today = DateTime.now();
    final hasVisitToday = detail.evolutions.any((item) {
      final date = DateTime.tryParse(item.visitedAt);
      return date != null &&
          date.year == today.year &&
          date.month == today.month &&
          date.day == today.day;
    });
    if (!hasVisitToday) return 'Registrar visita de hoje';
    if (detail.extensions.isNotEmpty) {
      final end = DateTime.tryParse(detail.extensions.first.endDate);
      if (end != null && end.difference(today).inDays <= 3) {
        return 'Revisar prorrogação próxima do vencimento';
      }
    }
    return 'Revisar evolução e plano terapêutico';
  }

  Future<void> _showFixedActions() async {
    await showModalBottomSheet<void>(
      context: context,
      builder:
          (sheetContext) => SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Wrap(
                runSpacing: 8,
                children: [
                  const ListTile(
                    title: Text(
                      'Ações da internação',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ),
                  ListTile(
                    leading: const Icon(Icons.event_repeat),
                    title: const Text('Nova prorrogação'),
                    onTap: () {
                      Navigator.pop(sheetContext);
                      _addExtension();
                    },
                  ),
                  ListTile(
                    leading: const Icon(Icons.playlist_add_check_circle),
                    title: const Text('Cadastrar TUSS'),
                    onTap: () {
                      Navigator.pop(sheetContext);
                      _addTuss();
                    },
                  ),
                  ListTile(
                    leading: const Icon(Icons.home_work_outlined),
                    title: const Text('Atualizar Home Care'),
                    onTap: () {
                      Navigator.pop(sheetContext);
                      _addHomeCareUpdate();
                    },
                  ),
                  ListTile(
                    leading: const Icon(Icons.logout),
                    title: const Text('Registrar alta'),
                    onTap: () {
                      Navigator.pop(sheetContext);
                      _addDischarge();
                    },
                  ),
                ],
              ),
            ),
          ),
    );
  }

  String _labelize(String value) {
    if (value.trim().isEmpty) return '-';
    if (value == 'hospital') return 'Prestador';
    return value
        .split('_')
        .map((part) {
          if (part == 'hospital') return 'Prestador';
          if (part.isEmpty) return part;
          return '${part[0].toUpperCase()}${part.substring(1)}';
        })
        .join(' ');
  }

  String _formatDate(String value) {
    final raw = value.trim();
    if (raw.isEmpty) return '';

    final datePart = raw.split(' ').first;
    final parts = datePart.split('-');
    if (parts.length != 3) {
      return raw;
    }

    final year = parts[0];
    final month = parts[1];
    final day = parts[2];
    if (year.length != 4 || month.length != 2 || day.length != 2) {
      return raw;
    }

    return '$day/$month/$year';
  }

  DateTime? _parseDisplayDate(String value) {
    final raw = value.trim();
    if (raw.isEmpty) return null;

    final parts = raw.split('/');
    if (parts.length != 3) return null;

    final day = int.tryParse(parts[0]);
    final month = int.tryParse(parts[1]);
    final year = int.tryParse(parts[2]);
    if (day == null || month == null || year == null) return null;

    return DateTime(year, month, day);
  }

  String _toApiDate(String value) {
    final parsed = _parseDisplayDate(value);
    if (parsed == null) return '';

    final year = parsed.year.toString().padLeft(4, '0');
    final month = parsed.month.toString().padLeft(2, '0');
    final day = parsed.day.toString().padLeft(2, '0');
    return '$year-$month-$day';
  }

  String _extensionPeriodText(ExtensionItem item) {
    final hasStart = item.startDate.isNotEmpty;
    final hasEnd = item.endDate.isNotEmpty;

    if (!hasStart && !hasEnd) {
      return 'Sem datas informadas';
    }
    if (hasStart && hasEnd) {
      return '${_formatDate(item.startDate)} até ${_formatDate(item.endDate)}';
    }
    if (hasStart) {
      return 'Início: ${_formatDate(item.startDate)}';
    }
    return 'Fim: ${_formatDate(item.endDate)}';
  }

  bool _hasExtensionSummary(ExtensionItem item) {
    return item.startDate.isNotEmpty ||
        item.endDate.isNotEmpty ||
        item.accommodation.isNotEmpty ||
        item.days > 0;
  }

  Future<void> _pickDate(
    BuildContext context,
    TextEditingController controller,
  ) async {
    final initial = _parseDisplayDate(controller.text.trim()) ?? DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      controller.text =
          '${picked.day.toString().padLeft(2, '0')}/${picked.month.toString().padLeft(2, '0')}/${picked.year.toString().padLeft(4, '0')}';
    }
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final detail = await widget.api.fetchAdmissionDetail(widget.admissionId);
      if (!mounted) return;
      setState(() => _detail = detail);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _addHomeCareUpdate() async {
    final detail = _detail;
    final patientName = detail?.admission.patientName ?? 'Paciente';
    final statusController = TextEditingController();
    final supplierController = TextEditingController();
    final modeController = TextEditingController();
    final expectedDateController = TextEditingController();
    final barrierController = TextEditingController();
    final transitionController = TextEditingController();
    final notesController = TextEditingController();
    bool saved = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      backgroundColor: const Color(0xFFF2F6FC),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder:
          (context) => StatefulBuilder(
            builder:
                (context, setModalState) => Padding(
                  padding: EdgeInsets.only(
                    left: 16,
                    right: 16,
                    top: 4,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 16,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    patientName,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontSize: 17,
                                      fontWeight: FontWeight.w800,
                                      color: Color(0xFF1D2940),
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  const Text(
                                    'Atualização de Home Care',
                                    style: TextStyle(
                                      fontSize: 13,
                                      color: Color(0xFF5B6577),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            IconButton(
                              onPressed: () => Navigator.of(context).pop(),
                              icon: const Icon(Icons.close),
                              tooltip: 'Fechar',
                              visualDensity: VisualDensity.compact,
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Container(
                          width: double.infinity,
                          height: 1,
                          color: const Color(0xFFD8E3F0),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue:
                              statusController.text.trim().isEmpty
                                  ? null
                                  : statusController.text.trim(),
                          items:
                              _homeCareStatusOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              statusController.text = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Status',
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue:
                              modeController.text.trim().isEmpty
                                  ? null
                                  : modeController.text.trim(),
                          items:
                              _homeCareModeOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              modeController.text = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Modalidade aprovada',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: supplierController,
                          decoration: const InputDecoration(
                            labelText: 'Fornecedor',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: expectedDateController,
                          readOnly: true,
                          onTap:
                              () => _pickDate(context, expectedDateController),
                          decoration: const InputDecoration(
                            labelText: 'Previsão de implantação',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        DropdownButtonFormField<String>(
                          initialValue:
                              barrierController.text.trim().isEmpty
                                  ? null
                                  : barrierController.text.trim(),
                          items:
                              _homeCareBarrierOptions
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(_labelize(item)),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              barrierController.text = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Barreira principal',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: transitionController,
                          minLines: 2,
                          maxLines: 4,
                          decoration: const InputDecoration(
                            labelText: 'Plano de transição',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: notesController,
                          minLines: 2,
                          maxLines: 4,
                          decoration: const InputDecoration(
                            labelText: 'Observações',
                            alignLabelWithHint: true,
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () async {
                            await widget.api.saveHomeCareUpdate(
                              admissionId: widget.admissionId,
                              status: statusController.text.trim(),
                              supplier: supplierController.text.trim(),
                              approvedMode: modeController.text.trim(),
                              expectedDate: _toApiDate(
                                expectedDateController.text.trim(),
                              ),
                              mainBarrier: barrierController.text.trim(),
                              transitionPlan: transitionController.text.trim(),
                              notes: notesController.text.trim(),
                            );
                            saved = true;
                            if (!context.mounted) return;
                            Navigator.of(context).pop();
                          },
                          style: FilledButton.styleFrom(
                            minimumSize: const Size.fromHeight(48),
                            backgroundColor: const Color(0xFF0F766E),
                          ),
                          child: const Text('Salvar Home Care'),
                        ),
                      ],
                    ),
                  ),
                ),
          ),
    );

    statusController.dispose();
    supplierController.dispose();
    modeController.dispose();
    expectedDateController.dispose();
    barrierController.dispose();
    transitionController.dispose();
    notesController.dispose();

    await _load();
    if (saved && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Home Care salvo com sucesso.')),
      );
    }
  }

  Future<void> _addTuss() async {
    final codeController = TextEditingController();
    final requestedController = TextEditingController(text: '1');
    final releasedController = TextEditingController(text: '0');
    final searchController = TextEditingController();
    final now = DateTime.now();
    final defaultTussDate =
        '${now.year.toString().padLeft(4, '0')}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';
    List<TussCatalogItem> catalog = const [];
    TussCatalogItem? selectedCatalogItem;
    String catalogError = '';
    bool saved = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            Future<void> searchCatalog() async {
              final query = searchController.text.trim();
              if (query.isEmpty) {
                setModalState(() {
                  catalog = const [];
                  catalogError = '';
                });
                return;
              }

              try {
                final items = await widget.api.searchTussCatalog(query);
                final unique = <String, TussCatalogItem>{};
                for (final item in items) {
                  final key = item.code.trim();
                  if (key.isEmpty || unique.containsKey(key)) {
                    continue;
                  }
                  unique[key] = item;
                }
                setModalState(() {
                  catalog = unique.values.toList();
                  catalogError = '';
                });
              } catch (error) {
                setModalState(() {
                  catalogError = error.toString().replaceFirst(
                    'Exception: ',
                    '',
                  );
                  catalog = const [];
                });
              }
            }

            return Padding(
              padding: EdgeInsets.only(
                left: 16,
                right: 16,
                top: 16,
                bottom: MediaQuery.of(context).viewInsets.bottom + 16,
              ),
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'Novo TUSS',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: searchController,
                      onChanged: (_) {
                        setModalState(() {
                          selectedCatalogItem = null;
                          codeController.clear();
                        });
                        searchCatalog();
                      },
                      decoration: InputDecoration(
                        labelText: 'Consultar TUSS',
                        suffixIcon: IconButton(
                          onPressed: searchCatalog,
                          icon: const Icon(Icons.search),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    if (catalogError.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Text(
                          catalogError,
                          style: const TextStyle(color: Colors.red),
                        ),
                      ),
                    if (selectedCatalogItem != null)
                      Container(
                        width: double.infinity,
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEEF4FB),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFD8E3F0)),
                        ),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'TUSS selecionado',
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF2D63A6),
                              ),
                            ),
                            const SizedBox(height: 6),
                            Text(
                              selectedCatalogItem!.code,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(selectedCatalogItem!.description),
                          ],
                        ),
                      )
                    else if (searchController.text.trim().isNotEmpty)
                      ...catalog
                          .take(4)
                          .map(
                            (item) => ListTile(
                              dense: true,
                              contentPadding: EdgeInsets.zero,
                              title: Text(item.code),
                              subtitle: Text(item.description),
                              onTap: () {
                                setModalState(() {
                                  selectedCatalogItem = item;
                                  codeController.text = item.code;
                                  searchController.text = item.code;
                                  catalog = const [];
                                });
                              },
                            ),
                          ),
                    TextField(
                      controller: codeController,
                      readOnly: true,
                      decoration: const InputDecoration(
                        labelText: 'Código TUSS',
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: requestedController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Qtd solicitada',
                      ),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: releasedController,
                      keyboardType: TextInputType.number,
                      decoration: const InputDecoration(
                        labelText: 'Qtd liberada',
                      ),
                    ),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: () async {
                        if (codeController.text.trim().isEmpty) {
                          setModalState(() {
                            catalogError =
                                'Selecione ou informe um código TUSS.';
                          });
                          return;
                        }

                        final createdTuss = await widget.api.createTuss(
                          admissionId: widget.admissionId,
                          code: codeController.text.trim(),
                          requestedQuantity:
                              int.tryParse(requestedController.text) ?? 1,
                          releasedQuantity:
                              int.tryParse(releasedController.text) ?? 0,
                          releasedFlag: 's',
                          performedAt: defaultTussDate,
                        );
                        if (mounted && _detail != null) {
                          final updated = [
                            createdTuss,
                            ..._detail!.tussItems.where(
                              (item) =>
                                  item.id != createdTuss.id &&
                                  item.code.trim().isNotEmpty,
                            ),
                          ];
                          setState(() {
                            _detail = AdmissionDetail(
                              admission: _detail!.admission,
                              tussItems: updated,
                              extensions: _detail!.extensions,
                              evolutions: _detail!.evolutions,
                              adverseEvents: _detail!.adverseEvents,
                            );
                          });
                        }
                        saved = true;
                        if (!context.mounted) return;
                        Navigator.of(context).pop();
                      },
                      child: const Text('Salvar TUSS'),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );

    await Future<void>.delayed(const Duration(milliseconds: 300));
    await _load();
    if (saved && mounted) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('TUSS salvo com sucesso.')));
    }
  }

  Future<void> _addExtension() async {
    final accommodationController = TextEditingController();
    final startDateController = TextEditingController();
    final endDateController = TextEditingController();
    bool saved = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder:
          (context) => Padding(
            padding: EdgeInsets.only(
              left: 16,
              right: 16,
              top: 16,
              bottom: MediaQuery.of(context).viewInsets.bottom + 16,
            ),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Nova prorrogação',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: accommodationController,
                    decoration: const InputDecoration(labelText: 'Acomodação'),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: startDateController,
                    readOnly: true,
                    onTap: () => _pickDate(context, startDateController),
                    decoration: const InputDecoration(
                      labelText: 'Data inicial',
                      suffixIcon: Icon(Icons.calendar_today),
                    ),
                  ),
                  const SizedBox(height: 8),
                  TextField(
                    controller: endDateController,
                    readOnly: true,
                    onTap: () => _pickDate(context, endDateController),
                    decoration: const InputDecoration(
                      labelText: 'Data final',
                      suffixIcon: Icon(Icons.calendar_today),
                    ),
                  ),
                  const SizedBox(height: 12),
                  FilledButton(
                    onPressed: () async {
                      final startDate = startDateController.text.trim();
                      final endDate = endDateController.text.trim();
                      final start = _parseDisplayDate(startDate);
                      final end = _parseDisplayDate(endDate);

                      if (start == null || end == null) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Selecione as duas datas.'),
                          ),
                        );
                        return;
                      }

                      final days = end.difference(start).inDays + 1;
                      if (days <= 0) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text(
                              'Data final deve ser maior ou igual à inicial.',
                            ),
                          ),
                        );
                        return;
                      }

                      final createdExtension = await widget.api.createExtension(
                        admissionId: widget.admissionId,
                        accommodation: accommodationController.text.trim(),
                        days: days,
                        startDate: _toApiDate(startDate),
                        endDate: _toApiDate(endDate),
                      );
                      if (mounted && _detail != null) {
                        setState(() {
                          _detail = AdmissionDetail(
                            admission: _detail!.admission,
                            tussItems: _detail!.tussItems,
                            extensions: [
                              createdExtension,
                              ..._detail!.extensions.where(
                                (item) => item.id != createdExtension.id,
                              ),
                            ],
                            evolutions: _detail!.evolutions,
                            adverseEvents: _detail!.adverseEvents,
                          );
                        });
                      }
                      saved = true;
                      if (!context.mounted) return;
                      Navigator.of(context).pop();
                    },
                    child: const Text('Salvar prorrogação'),
                  ),
                ],
              ),
            ),
          ),
    );

    await Future<void>.delayed(const Duration(milliseconds: 300));
    await _load();
    if (saved && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Prorrogação salva com sucesso.')),
      );
    }
  }

  Future<void> _addDischarge() async {
    final dateController = TextEditingController();
    final timeController = TextEditingController();
    final dischargeTypes = await widget.api.listDischargeTypes();
    if (!mounted) return;
    String selectedType = dischargeTypes.isNotEmpty ? dischargeTypes.first : '';

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder:
          (context) => StatefulBuilder(
            builder:
                (context, setModalState) => Padding(
                  padding: EdgeInsets.only(
                    left: 16,
                    right: 16,
                    top: 16,
                    bottom: MediaQuery.of(context).viewInsets.bottom + 16,
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Lançar alta',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<String>(
                          initialValue:
                              selectedType.isNotEmpty ? selectedType : null,
                          items:
                              dischargeTypes
                                  .map(
                                    (item) => DropdownMenuItem<String>(
                                      value: item,
                                      child: Text(item),
                                    ),
                                  )
                                  .toList(),
                          onChanged: (value) {
                            setModalState(() {
                              selectedType = value ?? '';
                            });
                          },
                          decoration: const InputDecoration(
                            labelText: 'Tipo de alta',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: dateController,
                          readOnly: true,
                          onTap: () => _pickDate(context, dateController),
                          decoration: const InputDecoration(
                            labelText: 'Data da alta',
                            suffixIcon: Icon(Icons.calendar_today),
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextField(
                          controller: timeController,
                          decoration: const InputDecoration(
                            labelText: 'Hora da alta (HH:MM)',
                          ),
                        ),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () async {
                            if (selectedType.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Selecione o tipo de alta.'),
                                ),
                              );
                              return;
                            }

                            final confirmed = await showDialog<bool>(
                              context: context,
                              builder:
                                  (dialogContext) => AlertDialog(
                                    icon: const Icon(
                                      Icons.warning_amber_rounded,
                                      color: Color(0xFFB54708),
                                    ),
                                    title: const Text('Confirmar alta?'),
                                    content: Text(
                                      'A internação será encerrada como “$selectedType”. Confira os dados antes de continuar.',
                                    ),
                                    actions: [
                                      TextButton(
                                        onPressed:
                                            () => Navigator.pop(
                                              dialogContext,
                                              false,
                                            ),
                                        child: const Text('Revisar'),
                                      ),
                                      FilledButton(
                                        onPressed:
                                            () => Navigator.pop(
                                              dialogContext,
                                              true,
                                            ),
                                        child: const Text('Confirmar alta'),
                                      ),
                                    ],
                                  ),
                            );
                            if (confirmed != true) return;

                            await widget.api.createDischarge(
                              admissionId: widget.admissionId,
                              type: selectedType,
                              date: _toApiDate(dateController.text.trim()),
                              time: timeController.text.trim(),
                            );
                            if (!context.mounted) return;
                            Navigator.of(context).pop();
                          },
                          child: const Text('Salvar alta'),
                        ),
                      ],
                    ),
                  ),
                ),
          ),
    );

    await _load();
  }

  Future<void> _openVisits({required bool showComposerInitially}) async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder:
            (_) => AdmissionEvolutionsPage(
              api: widget.api,
              admissionId: widget.admissionId,
              patientName: _detail?.admission.patientName ?? 'Internação',
              showComposerInitially: showComposerInitially,
            ),
      ),
    );
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final detail = _detail;

    return Scaffold(
      appBar: AppBar(title: const Text('Detalhe da internação')),
      bottomNavigationBar:
          detail == null
              ? null
              : SafeArea(
                child: Material(
                  elevation: 12,
                  color: Theme.of(context).colorScheme.surface,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 10, 16, 10),
                    child: Row(
                      children: [
                        Expanded(
                          child: FilledButton.icon(
                            onPressed:
                                () => _openVisits(showComposerInitially: true),
                            icon: const Icon(Icons.note_add_outlined),
                            label: const Text('Nova visita'),
                          ),
                        ),
                        const SizedBox(width: 10),
                        OutlinedButton.icon(
                          onPressed: _showFixedActions,
                          icon: const Icon(Icons.grid_view_rounded),
                          label: const Text('Ações'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
      body:
          _loading
              ? const Center(child: CircularProgressIndicator())
              : detail == null
              ? const Center(child: Text('Internação não encontrada.'))
              : RefreshIndicator(
                onRefresh: _load,
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF234F80), Color(0xFF3479B6)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Padding(
                        padding: const EdgeInsets.all(18),
                        child: DefaultTextStyle(
                          style: const TextStyle(
                            color: Color(0xFFE5F1FC),
                            fontSize: 13.5,
                            height: 1.25,
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                detail.admission.patientName,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 19,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 10),
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  _CaseBadge(
                                    label:
                                        '${_admissionDays(detail.admission.admissionDate)} dias internado',
                                    backgroundColor: Colors.white.withValues(
                                      alpha: .16,
                                    ),
                                    textColor: Colors.white,
                                  ),
                                  _CaseBadge(
                                    label:
                                        detail.admission.dischargeDate.isEmpty
                                            ? 'Ativa'
                                            : 'Alta registrada',
                                    backgroundColor:
                                        detail.admission.dischargeDate.isEmpty
                                            ? const Color(0xFFBCE7D8)
                                            : const Color(0xFFD9E0E8),
                                    textColor: const Color(0xFF174A3B),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 10),
                              if (detail.admission.hospitalName.isNotEmpty)
                                Text(
                                  'Prestador: ${detail.admission.hospitalName}',
                                ),
                              if (detail.admission.insuranceName.isNotEmpty)
                                Text(
                                  'Convênio: ${detail.admission.insuranceName}',
                                ),
                              if (detail.admission.cidCode.isNotEmpty)
                                Text('CID: ${detail.admission.cidCode}'),
                              if (detail.admission.authorizationCode.isNotEmpty)
                                Text(
                                  'Senha: ${detail.admission.authorizationCode}',
                                ),
                              Text(
                                'Data: ${detail.admission.admissionDate.isEmpty ? "-" : _formatDate(detail.admission.admissionDate)}',
                              ),
                              if (detail.admission.dischargeDate.isNotEmpty)
                                Text(
                                  'Alta: ${_formatDate(detail.admission.dischargeDate)}',
                                ),
                              if (detail.admission.dischargeType.isNotEmpty)
                                Text(
                                  'Tipo alta: ${detail.admission.dischargeType}',
                                ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF4DE),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFF0CF8E)),
                      ),
                      child: Row(
                        children: [
                          const CircleAvatar(
                            backgroundColor: Color(0xFFFFE4AD),
                            foregroundColor: Color(0xFF8B5E1A),
                            child: Icon(Icons.lightbulb_outline),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Próxima ação sugerida',
                                  style: TextStyle(
                                    color: Color(0xFF7A571A),
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                Text(
                                  _nextAction(detail),
                                  style: const TextStyle(
                                    color: Color(0xFF4A3512),
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Visita do paciente',
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF1D2940),
                      ),
                    ),
                    const SizedBox(height: 10),
                    _VisitActionsCard(
                      onNewVisit:
                          () => _openVisits(showComposerInitially: true),
                      onHistory:
                          () => _openVisits(showComposerInitially: false),
                    ),
                    const SizedBox(height: 14),
                    const Text(
                      'Subitens da internação',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF5B6577),
                      ),
                    ),
                    const SizedBox(height: 8),
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 9,
                      crossAxisSpacing: 9,
                      childAspectRatio: 2.45,
                      children: [
                        _ActionTile(
                          label: 'Home Care',
                          subtitle: 'Atualizar',
                          icon: Icons.home_work_outlined,
                          backgroundColor: const Color(0xFFECFDF5),
                          accentColor: const Color(0xFF0F766E),
                          onTap: _addHomeCareUpdate,
                        ),
                        _ActionTile(
                          label: 'Prorrogação',
                          subtitle: 'Lançar nova',
                          icon: Icons.event_repeat,
                          backgroundColor: const Color(0xFFF6F0FB),
                          accentColor: const Color(0xFF5E2363),
                          onTap: _addExtension,
                        ),
                        _ActionTile(
                          label: 'TUSS',
                          subtitle: 'Cadastrar item',
                          icon: Icons.playlist_add_check_circle,
                          backgroundColor: const Color(0xFFEEF4FB),
                          accentColor: const Color(0xFF2D63A6),
                          onTap: _addTuss,
                        ),
                        _ActionTile(
                          label: 'Alta',
                          subtitle: 'Registrar saída',
                          icon: Icons.logout,
                          backgroundColor: const Color(0xFFECFDF5),
                          accentColor: const Color(0xFF0F766E),
                          onTap: _addDischarge,
                        ),
                        _ActionTile(
                          label: 'Evento adverso',
                          subtitle: 'Abrir módulo',
                          icon: Icons.warning_amber_rounded,
                          backgroundColor: const Color(0xFFFFF8EC),
                          accentColor: const Color(0xFF8B5E1A),
                          onTap: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(
                                builder:
                                    (_) => AdverseEventCasesPage(
                                      api: widget.api,
                                      initialQuery:
                                          detail.admission.patientName,
                                    ),
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                    if (detail.extensions.isNotEmpty &&
                        _hasExtensionSummary(detail.extensions.first)) ...[
                      const SizedBox(height: 12),
                      Card(
                        color: const Color(0xFFF6F0FB),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Última prorrogação',
                                style: TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                _extensionPeriodText(detail.extensions.first),
                              ),
                              Text('Diárias: ${detail.extensions.first.days}'),
                              Text(
                                'Acomodação: ${detail.extensions.first.accommodation.isEmpty ? "-" : detail.extensions.first.accommodation}',
                              ),
                            ],
                          ),
                        ),
                      ),
                    ],
                    const SizedBox(height: 12),
                    _SectionCard(
                      title: 'Linha do tempo',
                      count: _timeline(detail).length,
                      children:
                          _timeline(detail)
                              .take(12)
                              .map(
                                (entry) => ListTile(
                                  dense: true,
                                  contentPadding: EdgeInsets.zero,
                                  leading: CircleAvatar(
                                    radius: 18,
                                    backgroundColor: entry.color.withValues(
                                      alpha: .12,
                                    ),
                                    foregroundColor: entry.color,
                                    child: Icon(entry.icon, size: 19),
                                  ),
                                  title: Text(
                                    entry.title,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                  subtitle: Text(
                                    '${_formatDate(entry.date.toIso8601String())} • ${entry.subtitle}',
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              )
                              .toList(),
                    ),
                    const SizedBox(height: 12),
                    _SectionCard(
                      title: 'TUSS',
                      count: detail.tussItems.length,
                      children:
                          detail.tussItems
                              .where((item) => item.code.trim().isNotEmpty)
                              .map(
                                (item) => ListTile(
                                  dense: true,
                                  visualDensity: VisualDensity.compact,
                                  contentPadding: EdgeInsets.zero,
                                  title: Text(
                                    '${item.code} • ${item.description}',
                                  ),
                                  subtitle: Text(
                                    'Solicitado: ${item.requestedQuantity} • Liberado: ${item.releasedQuantity} • Status: ${item.releasedFlag}\n'
                                    'Data liberação: ${item.releasedAt.isEmpty ? "-" : _formatDate(item.releasedAt)} • '
                                    'Por: ${item.releasedBy.trim().isEmpty ? "-" : item.releasedBy.trim()}',
                                  ),
                                ),
                              )
                              .toList(),
                    ),
                    const SizedBox(height: 12),
                    _SectionCard(
                      title: 'Prorrogações',
                      count: detail.extensions.length,
                      children:
                          detail.extensions
                              .map(
                                (item) => ListTile(
                                  dense: true,
                                  visualDensity: VisualDensity.compact,
                                  contentPadding: EdgeInsets.zero,
                                  title: Text(_extensionPeriodText(item)),
                                  subtitle: Text(
                                    'Diárias: ${item.days} • Acomodação: ${item.accommodation.isEmpty ? "-" : item.accommodation}',
                                  ),
                                ),
                              )
                              .toList(),
                    ),
                  ],
                ),
              ),
    );
  }
}

class _FullCareMenuRow extends StatelessWidget {
  const _FullCareMenuRow({
    required this.icon,
    required this.title,
    required this.subtitle,
    this.iconBackgroundColor = const Color(0xFFE8F1FB),
    this.accentColor = const Color(0xFF2D63A6),
    this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color iconBackgroundColor;
  final Color accentColor;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final isIOS = Theme.of(context).platform == TargetPlatform.iOS;
    return Card(
      margin: const EdgeInsets.only(bottom: 7),
      elevation: 3,
      shadowColor: accentColor.withValues(alpha: 0.22),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: IntrinsicHeight(
          child: Row(
            children: [
              Container(
                width: 5,
                decoration: BoxDecoration(
                  color: accentColor,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(12),
                    bottomLeft: Radius.circular(12),
                  ),
                ),
              ),
              Expanded(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(minHeight: 70),
                  child: Row(
                    children: [
                      Padding(
                        padding: const EdgeInsets.fromLTRB(13, 0, 0, 0),
                        child: Container(
                          width: 38,
                          height: 38,
                          decoration: BoxDecoration(
                            color: iconBackgroundColor,
                            borderRadius: BorderRadius.circular(13),
                          ),
                          child: Icon(icon, color: accentColor, size: 22),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              title,
                              style: TextStyle(
                                fontSize: isIOS ? 13 : 15.5,
                                fontWeight: FontWeight.w800,
                                color: Theme.of(context).colorScheme.onSurface,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              subtitle,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: TextStyle(
                                fontSize: isIOS ? 9.8 : 11,
                                height: 1.25,
                                color:
                                    Theme.of(
                                      context,
                                    ).colorScheme.onSurfaceVariant,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              if (onTap != null)
                Padding(
                  padding: const EdgeInsets.only(right: 10, left: 8),
                  child: Icon(
                    Icons.chevron_right,
                    color: accentColor.withValues(alpha: 0.75),
                    size: 28,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SecondaryMenuRow extends StatelessWidget {
  const _SecondaryMenuRow({
    required this.icon,
    required this.title,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final isIOS = Theme.of(context).platform == TargetPlatform.iOS;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        child: Row(
          children: [
            Icon(
              icon,
              size: 18,
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                title,
                style: TextStyle(
                  fontSize: isIOS ? 11.5 : 13,
                  fontWeight: FontWeight.w700,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
            ),
            Icon(
              Icons.chevron_right,
              size: 18,
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ],
        ),
      ),
    );
  }
}

class _CompactInfoLine extends StatelessWidget {
  const _CompactInfoLine({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(icon, size: 14, color: const Color(0xFF5E2363)),
        const SizedBox(width: 5),
        Expanded(
          child: Text(
            text,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 12,
              color: Theme.of(context).colorScheme.onSurfaceVariant,
              height: 1.15,
            ),
          ),
        ),
      ],
    );
  }
}

class _InfoMetricCard extends StatelessWidget {
  const _InfoMetricCard({
    required this.label,
    required this.value,
    required this.accentColor,
  });

  final String label;
  final String value;
  final Color accentColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFD8E3F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              color: Color(0xFF5B6577),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: accentColor,
            ),
          ),
        ],
      ),
    );
  }
}

class _CaseBadge extends StatelessWidget {
  const _CaseBadge({
    required this.label,
    required this.backgroundColor,
    required this.textColor,
  });

  final String label;
  final Color backgroundColor;
  final Color textColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: textColor,
          fontSize: 12,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _VisitActionsCard extends StatelessWidget {
  const _VisitActionsCard({required this.onNewVisit, required this.onHistory});

  final VoidCallback onNewVisit;
  final VoidCallback onHistory;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: const Color(0xFFFFF8EC),
      elevation: 2.5,
      shadowColor: const Color(0xFF8B5E1A).withValues(alpha: 0.18),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          children: [
            Expanded(
              child: _VisitActionButton(
                icon: Icons.add_circle_outline,
                label: 'Nova visita',
                onTap: onNewVisit,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _VisitActionButton(
                icon: Icons.history,
                label: 'Visitas anteriores',
                onTap: onHistory,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _VisitActionButton extends StatelessWidget {
  const _VisitActionButton({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      onPressed: onTap,
      style: OutlinedButton.styleFrom(
        foregroundColor: const Color(0xFF8B5E1A),
        side: const BorderSide(color: Color(0xFFD9BC8B)),
        minimumSize: const Size.fromHeight(52),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      ),
      icon: Icon(icon, size: 21),
      label: Text(
        label,
        textAlign: TextAlign.center,
        maxLines: 2,
        style: const TextStyle(fontWeight: FontWeight.w800),
      ),
    );
  }
}

class _ActionTile extends StatelessWidget {
  const _ActionTile({
    required this.label,
    required this.subtitle,
    required this.icon,
    required this.backgroundColor,
    required this.accentColor,
    required this.onTap,
  });

  final String label;
  final String subtitle;
  final IconData icon;
  final Color backgroundColor;
  final Color accentColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: backgroundColor,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 10),
          child: Row(
            children: [
              Container(
                width: 32,
                height: 32,
                decoration: BoxDecoration(
                  color: accentColor.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(11),
                ),
                child: Icon(icon, color: accentColor, size: 20),
              ),
              const SizedBox(width: 9),
              Expanded(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      label,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: accentColor,
                        height: 1.1,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontSize: 10.5,
                        height: 1.1,
                        color: Color(0xFF5B6577),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  const _SectionCard({
    required this.title,
    required this.count,
    required this.children,
  });

  final String title;
  final int count;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(width: 6),
                CircleAvatar(
                  radius: 10,
                  backgroundColor: const Color(0xFFEEF4FB),
                  child: Text(
                    '$count',
                    style: const TextStyle(
                      fontSize: 11,
                      color: Color(0xFF2D63A6),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 6),
            if (children.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 4),
                child: Text(
                  'Nenhum registro encontrado.',
                  style: TextStyle(fontSize: 13, color: Color(0xFF5B6577)),
                ),
              )
            else
              ...children,
          ],
        ),
      ),
    );
  }
}

class AdmissionEvolutionsPage extends StatefulWidget {
  const AdmissionEvolutionsPage({
    super.key,
    required this.api,
    required this.admissionId,
    required this.patientName,
    this.showComposerInitially = true,
  });

  final MobileApi api;
  final int admissionId;
  final String patientName;
  final bool showComposerInitially;

  @override
  State<AdmissionEvolutionsPage> createState() =>
      _AdmissionEvolutionsPageState();
}

class _AdmissionEvolutionsPageState extends State<AdmissionEvolutionsPage> {
  static const _pendingVisitsKey = 'fullcare_pending_visits';
  final _reportController = TextEditingController();
  final _therapeuticPlanController = TextEditingController();
  final _reportFocusNode = FocusNode();
  final _therapeuticPlanFocusNode = FocusNode();
  List<EvolutionItem> _items = const [];
  bool _loading = true;
  bool _saving = false;
  Timer? _draftDebounce;
  String _saveStatus = '';
  bool _hasPendingSync = false;
  late bool _showComposer;

  String get _draftReportKey =>
      'fullcare_visit_draft_${widget.admissionId}_report';
  String get _draftPlanKey => 'fullcare_visit_draft_${widget.admissionId}_plan';

  String _formatDateTime(String value) {
    final raw = value.trim();
    if (raw.isEmpty) return '-';

    final parts = raw.split(' ');
    final datePart = parts.first;
    final dateBits = datePart.split('-');
    if (dateBits.length != 3) {
      return raw;
    }

    final formattedDate = '${dateBits[2]}/${dateBits[1]}/${dateBits[0]}';
    if (parts.length < 2) {
      return formattedDate;
    }

    final timePart = parts[1];
    final timeBits = timePart.split(':');
    if (timeBits.length < 2) {
      return formattedDate;
    }

    return '$formattedDate ${timeBits[0]}:${timeBits[1]}';
  }

  @override
  void initState() {
    super.initState();
    _showComposer = widget.showComposerInitially;
    _reportController.addListener(_draftChanged);
    _therapeuticPlanController.addListener(_draftChanged);
    _restoreDraft();
    _load();
    _syncPendingVisits();
  }

  @override
  void dispose() {
    _draftDebounce?.cancel();
    _reportController.removeListener(_draftChanged);
    _therapeuticPlanController.removeListener(_draftChanged);
    _reportController.dispose();
    _therapeuticPlanController.dispose();
    _reportFocusNode.dispose();
    _therapeuticPlanFocusNode.dispose();
    super.dispose();
  }

  Future<void> _restoreDraft() async {
    final prefs = await SharedPreferences.getInstance();
    final report = prefs.getString(_draftReportKey) ?? '';
    final plan = prefs.getString(_draftPlanKey) ?? '';
    if (!mounted) return;
    _reportController.text = report;
    _therapeuticPlanController.text = plan;
    if (report.isNotEmpty || plan.isNotEmpty) {
      setState(() => _saveStatus = 'Rascunho recuperado');
    }
  }

  void _draftChanged() {
    if (!mounted) return;
    setState(() => _saveStatus = 'Salvando rascunho...');
    _draftDebounce?.cancel();
    _draftDebounce = Timer(const Duration(milliseconds: 500), () async {
      final prefs = await SharedPreferences.getInstance();
      await Future.wait([
        prefs.setString(_draftReportKey, _reportController.text),
        prefs.setString(_draftPlanKey, _therapeuticPlanController.text),
      ]);
      if (mounted) setState(() => _saveStatus = 'Rascunho salvo');
    });
  }

  Future<void> _clearDraft() async {
    final prefs = await SharedPreferences.getInstance();
    await Future.wait([
      prefs.remove(_draftReportKey),
      prefs.remove(_draftPlanKey),
    ]);
  }

  Future<List<Map<String, dynamic>>> _pendingVisits() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_pendingVisitsKey);
    if (raw == null || raw.isEmpty) return [];
    try {
      return (jsonDecode(raw) as List<dynamic>)
          .map((item) => item as Map<String, dynamic>)
          .toList();
    } catch (_) {
      return [];
    }
  }

  Future<void> _queuePendingVisit(String report, String plan) async {
    final prefs = await SharedPreferences.getInstance();
    final pending = await _pendingVisits();
    pending.removeWhere(
      (item) => (item['admission_id'] as int? ?? 0) == widget.admissionId,
    );
    pending.add({
      'admission_id': widget.admissionId,
      'patient_name': widget.patientName,
      'report': report,
      'therapeutic_plan': plan,
      'saved_at': DateTime.now().toIso8601String(),
    });
    await prefs.setString(_pendingVisitsKey, jsonEncode(pending));
  }

  Future<void> _syncPendingVisits() async {
    final pending = await _pendingVisits();
    final own = pending.where(
      (item) => (item['admission_id'] as int? ?? 0) == widget.admissionId,
    );
    if (own.isEmpty) return;
    if (mounted) setState(() => _hasPendingSync = true);
    final item = own.first;
    try {
      await widget.api.saveEvolution(
        admissionId: widget.admissionId,
        report: item['report'] as String? ?? '',
        therapeuticPlan: item['therapeutic_plan'] as String? ?? '',
      );
      pending.removeWhere(
        (entry) => (entry['admission_id'] as int? ?? 0) == widget.admissionId,
      );
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(_pendingVisitsKey, jsonEncode(pending));
      if (!mounted) return;
      setState(() {
        _hasPendingSync = false;
        _saveStatus = 'Sincronizado';
      });
      await _load();
    } catch (_) {
      if (mounted) setState(() => _saveStatus = 'Aguardando internet');
    }
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final items = await widget.api.listEvolutions(widget.admissionId);
      if (!mounted) return;
      setState(() => _items = items);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(_friendlyError(error))));
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  void _focusForDictation(FocusNode focusNode) {
    FocusScope.of(context).requestFocus(focusNode);
  }

  Future<void> _saveEvolution() async {
    final report = _reportController.text.trim();
    final therapeuticPlan = _therapeuticPlanController.text.trim();

    if (report.isEmpty) {
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Informe a visita.')));
      return;
    }

    setState(() => _saving = true);
    try {
      final item = await widget.api.saveEvolution(
        admissionId: widget.admissionId,
        report: report,
        therapeuticPlan: therapeuticPlan,
      );

      if (!mounted) return;
      setState(() {
        _items = [item, ..._items.where((existing) => existing.id != item.id)];
        _reportController.clear();
        _therapeuticPlanController.clear();
        _saveStatus = 'Salvo';
      });
      await _clearDraft();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Visita salva com sucesso.')),
      );
      await _load();
    } catch (error) {
      if (!mounted) return;
      final message = _friendlyError(error);
      final networkFailure =
          message.toLowerCase().contains('conectar') ||
          message.toLowerCase().contains('conexao') ||
          message.toLowerCase().contains('servidor');
      if (networkFailure) {
        await _queuePendingVisit(report, therapeuticPlan);
        await _clearDraft();
        _reportController.clear();
        _therapeuticPlanController.clear();
        if (!mounted) return;
        setState(() {
          _hasPendingSync = true;
          _saveStatus = 'Aguardando internet';
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Sem conexão. A visita foi guardada e será sincronizada automaticamente.',
            ),
          ),
        );
      } else {
        setState(() => _saveStatus = 'Falha ao salvar');
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('$message Revise os campos e tente novamente.'),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Visitas')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            SegmentedButton<bool>(
              segments: const [
                ButtonSegment<bool>(
                  value: true,
                  icon: Icon(Icons.add_circle_outline),
                  label: Text('Nova visita'),
                ),
                ButtonSegment<bool>(
                  value: false,
                  icon: Icon(Icons.history),
                  label: Text('Anteriores'),
                ),
              ],
              selected: {_showComposer},
              showSelectedIcon: false,
              onSelectionChanged:
                  (selection) => setState(() {
                    _showComposer = selection.first;
                  }),
            ),
            const SizedBox(height: 12),
            if (_showComposer)
              Card(
                color: const Color(0xFFFFF8EC),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        widget.patientName,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 6),
                      const Text(
                        'Visita clínica e programação terapêutica',
                        style: TextStyle(
                          color: Color(0xFF5B6577),
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 12),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.edit_note, color: Color(0xFF8B5E1A)),
                        SizedBox(width: 8),
                        Text(
                          'Nova visita',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF1D2940),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _reportController,
                      focusNode: _reportFocusNode,
                      keyboardType: TextInputType.multiline,
                      textCapitalization: TextCapitalization.sentences,
                      maxLines: 7,
                      minLines: 5,
                      maxLength: 5000,
                      decoration: InputDecoration(
                        labelText: 'Visita / Relatório',
                        alignLabelWithHint: true,
                        suffixIcon: IconButton(
                          onPressed: () => _focusForDictation(_reportFocusNode),
                          icon: const Icon(Icons.mic_none),
                          tooltip: 'Ditar',
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _therapeuticPlanController,
                      focusNode: _therapeuticPlanFocusNode,
                      keyboardType: TextInputType.multiline,
                      textCapitalization: TextCapitalization.sentences,
                      maxLines: 5,
                      minLines: 3,
                      maxLength: 3000,
                      decoration: InputDecoration(
                        labelText: 'Programação terapêutica',
                        alignLabelWithHint: true,
                        suffixIcon: IconButton(
                          onPressed:
                              () =>
                                  _focusForDictation(_therapeuticPlanFocusNode),
                          icon: const Icon(Icons.mic_none),
                          tooltip: 'Ditar',
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    if (_saveStatus.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Row(
                          children: [
                            Icon(
                              _saveStatus == 'Falha ao salvar'
                                  ? Icons.error_outline
                                  : _hasPendingSync
                                  ? Icons.cloud_upload_outlined
                                  : Icons.cloud_done_outlined,
                              size: 18,
                              color:
                                  _saveStatus == 'Falha ao salvar'
                                      ? const Color(0xFFB42318)
                                      : _hasPendingSync
                                      ? const Color(0xFF9A6700)
                                      : const Color(0xFF0F766E),
                            ),
                            const SizedBox(width: 7),
                            Expanded(
                              child: Text(
                                _saveStatus,
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            if (_hasPendingSync)
                              TextButton(
                                onPressed: _syncPendingVisits,
                                child: const Text('Sincronizar'),
                              ),
                          ],
                        ),
                      ),
                    SizedBox(
                      width: double.infinity,
                      child: FilledButton.icon(
                        onPressed: _saving ? null : _saveEvolution,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(48),
                          backgroundColor: const Color(0xFF8B5E1A),
                        ),
                        icon:
                            _saving
                                ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                                : const Icon(Icons.save_outlined),
                        label: Text(_saving ? 'Salvando...' : 'Salvar visita'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            if (_showComposer) const SizedBox(height: 14),
            Row(
              children: [
                const Expanded(
                  child: Text(
                    'Visitas anteriores',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1D2940),
                    ),
                  ),
                ),
                _CaseBadge(
                  label: '${_items.length}',
                  backgroundColor: const Color(0xFFEEF4FB),
                  textColor: const Color(0xFF2D63A6),
                ),
              ],
            ),
            const SizedBox(height: 10),
            if (_loading)
              const Center(
                child: Padding(
                  padding: EdgeInsets.all(24),
                  child: CircularProgressIndicator(),
                ),
              )
            else if (_items.isEmpty)
              const Card(
                child: Padding(
                  padding: EdgeInsets.all(16),
                  child: Text('Nenhuma visita registrada.'),
                ),
              )
            else
              ..._items.map(
                (item) => Card(
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                _formatDateTime(item.visitedAt),
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                            Text(
                              'Visita ${item.visitNumber}',
                              style: const TextStyle(
                                color: Color(0xFF8B5E1A),
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          item.createdBy.isEmpty ? '-' : item.createdBy,
                          style: TextStyle(color: Colors.blueGrey.shade700),
                        ),
                        const SizedBox(height: 10),
                        Text(
                          item.report.trim().isEmpty ? '-' : item.report.trim(),
                        ),
                        if (item.therapeuticPlan.trim().isNotEmpty) ...[
                          const SizedBox(height: 12),
                          const Text(
                            'Programação terapêutica',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF8B5E1A),
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(item.therapeuticPlan.trim()),
                        ],
                      ],
                    ),
                  ),
                ),
              ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }
}
