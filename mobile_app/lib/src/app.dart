import 'package:flutter/material.dart';
import 'package:mobile_app/src/mobile_api.dart';
import 'package:mobile_app/src/models.dart';

class FullCareMobileApp extends StatefulWidget {
  const FullCareMobileApp({super.key});

  @override
  State<FullCareMobileApp> createState() => _FullCareMobileAppState();
}

class _FullCareMobileAppState extends State<FullCareMobileApp> {
  final MobileApi _api = MobileApi();
  SessionUser? _user;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
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

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'FullCare Mobile',
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
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Color(0xFFD8E3F0)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16),
            borderSide: const BorderSide(color: Color(0xFFD8E3F0)),
          ),
        ),
      ),
      home: _loading
          ? const Scaffold(body: Center(child: CircularProgressIndicator()))
          : (_user == null
              ? LoginPage(onLogin: _handleLogin)
              : HomePage(api: _api, user: _user!, onLogout: _handleLogout)),
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

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    try {
      await widget.onLogin(
        _emailController.text.trim(),
        _passwordController.text,
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => _submitting = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF2D63A6), Color(0xFF92BEE2), Color(0xFF5E2363)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(20),
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
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 56,
                          height: 56,
                          decoration: BoxDecoration(
                            color: const Color(0xFF2D63A6),
                            borderRadius: BorderRadius.circular(18),
                          ),
                          child: const Icon(Icons.health_and_safety,
                              color: Colors.white),
                        ),
                        const SizedBox(height: 18),
                        Text(
                          'FullCare Mobile',
                          style: Theme.of(context)
                              .textTheme
                              .headlineSmall
                              ?.copyWith(fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Internacao, TUSS e prorrogacao com dados do sistema web.',
                        ),
                        const SizedBox(height: 20),
                        TextField(
                          controller: _emailController,
                          keyboardType: TextInputType.emailAddress,
                          decoration: const InputDecoration(labelText: 'E-mail'),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _passwordController,
                          obscureText: true,
                          decoration: const InputDecoration(labelText: 'Senha'),
                        ),
                        const SizedBox(height: 18),
                        FilledButton(
                          style: FilledButton.styleFrom(
                            backgroundColor: const Color(0xFF5E2363),
                            minimumSize: const Size.fromHeight(52),
                          ),
                          onPressed: _submitting ? null : _submit,
                          child: _submitting
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
    );
  }
}

class HomePage extends StatefulWidget {
  const HomePage({
    super.key,
    required this.api,
    required this.user,
    required this.onLogout,
  });

  final MobileApi api;
  final SessionUser user;
  final Future<void> Function() onLogout;

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('FullCare Mobile'),
        actions: [
          IconButton(
            onPressed: () async {
              await widget.onLogout();
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: Column(
        children: [
          Container(
            width: double.infinity,
            color: const Color(0xFFEEF4FB),
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 10),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  widget.user.name,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1D2940),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${widget.user.email} • ${widget.user.roleName}',
                  style: const TextStyle(color: Color(0xFF64748B)),
                ),
              ],
            ),
          ),
          Expanded(child: AdmissionsPage(api: widget.api)),
        ],
      ),
    );
  }
}

class AdmissionsPage extends StatefulWidget {
  const AdmissionsPage({super.key, required this.api});

  final MobileApi api;

  @override
  State<AdmissionsPage> createState() => _AdmissionsPageState();
}

class _AdmissionsPageState extends State<AdmissionsPage> {
  final _searchController = TextEditingController();
  List<AdmissionItem> _items = const [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load([String query = '']) async {
    setState(() => _loading = true);
    try {
      final items = await widget.api.listAdmissions(query);
      if (!mounted) return;
      setState(() => _items = items);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  void dispose() {
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
              labelText: 'Pesquisar por paciente ou hospital',
              suffixIcon: IconButton(
                onPressed: () => _load(_searchController.text.trim()),
                icon: const Icon(Icons.search),
              ),
            ),
          ),
          const SizedBox(height: 10),
          Text(
            'Total de internados: ${_items.length}',
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Color(0xFF2D63A6),
            ),
          ),
          const SizedBox(height: 16),
          if (_loading)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: CircularProgressIndicator(),
              ),
            )
          else ..._items.map(
            (item) => Card(
              child: ListTile(
                contentPadding: const EdgeInsets.all(16),
                title: Text(item.patientName),
                subtitle: Text(
                  'Hospital: ${item.hospitalName.isEmpty ? "-" : item.hospitalName}\nConvênio: ${item.insuranceName.isEmpty ? "-" : item.insuranceName}\nCID: ${item.cidCode.isEmpty ? "-" : item.cidCode}\nSenha: ${item.authorizationCode.isEmpty ? "-" : item.authorizationCode}',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () async {
                  await Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => AdmissionDetailPage(
                        api: widget.api,
                        admissionId: item.id,
                      ),
                    ),
                  );
                  _load(_searchController.text.trim());
                },
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

class _AdmissionDetailPageState extends State<AdmissionDetailPage> {
  AdmissionDetail? _detail;
  bool _loading = true;

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
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
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
                  catalogError =
                      error.toString().replaceFirst('Exception: ', '');
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
                    const Text('Novo TUSS',
                        style: TextStyle(
                            fontSize: 18, fontWeight: FontWeight.w700)),
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
                      ...catalog.take(4).map(
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
                      decoration:
                          const InputDecoration(labelText: 'Qtd solicitada'),
                    ),
                    const SizedBox(height: 8),
                    TextField(
                      controller: releasedController,
                      keyboardType: TextInputType.number,
                      decoration:
                          const InputDecoration(labelText: 'Qtd liberada'),
                    ),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: () async {
                        if (codeController.text.trim().isEmpty) {
                          setModalState(() {
                            catalogError = 'Selecione ou informe um código TUSS.';
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
                              (item) => item.id != createdTuss.id && item.code.trim().isNotEmpty,
                            ),
                          ];
                          setState(() {
                            _detail = AdmissionDetail(
                              admission: _detail!.admission,
                              tussItems: updated,
                              extensions: _detail!.extensions,
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
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('TUSS salvo com sucesso.')),
      );
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
      builder: (context) => Padding(
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
                const Text('Nova prorrogação',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
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
                        const SnackBar(content: Text('Selecione as duas datas.')),
                      );
                      return;
                    }

                    final days = end.difference(start).inDays + 1;
                    if (days <= 0) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Data final deve ser maior ou igual à inicial.')),
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
                            ..._detail!.extensions.where((item) => item.id != createdExtension.id),
                          ],
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
          )),
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
    String selectedType =
        dischargeTypes.isNotEmpty ? dischargeTypes.first : '';

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
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
                const Text('Lançar alta',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  value: selectedType.isNotEmpty ? selectedType : null,
                  items: dischargeTypes
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
                  decoration: const InputDecoration(labelText: 'Tipo de alta'),
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
                  decoration:
                      const InputDecoration(labelText: 'Hora da alta (HH:MM)'),
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

  Future<void> _addEvolution() async {
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => AdmissionEvolutionsPage(
          api: widget.api,
          admissionId: widget.admissionId,
          patientName: _detail?.admission.patientName ?? 'Internação',
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
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : detail == null
              ? const Center(child: Text('Internação não encontrada.'))
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                detail.admission.patientName,
                                style: const TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text('Hospital: ${detail.admission.hospitalName}'),
                              Text('Convênio: ${detail.admission.insuranceName}'),
                              Text('CID: ${detail.admission.cidCode}'),
                              Text(
                                  'Senha: ${detail.admission.authorizationCode}'),
                              Text(
                                  'Data: ${detail.admission.admissionDate.isEmpty ? "-" : _formatDate(detail.admission.admissionDate)}'),
                              Text(
                                  'Alta: ${detail.admission.dischargeDate.isEmpty ? "Sem alta" : _formatDate(detail.admission.dischargeDate)}'),
                              if (detail.admission.dischargeType.isNotEmpty)
                                Text('Tipo alta: ${detail.admission.dischargeType}'),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      const Text(
                        'Ações da internação',
                        style: TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF1D2940),
                        ),
                      ),
                      const SizedBox(height: 10),
                      GridView.count(
                        crossAxisCount: 2,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        mainAxisSpacing: 10,
                        crossAxisSpacing: 10,
                        childAspectRatio: 1.45,
                        children: [
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
                            label: 'Evolução',
                            subtitle: 'Ver histórico',
                            icon: Icons.edit_note,
                            backgroundColor: const Color(0xFFFFF8EC),
                            accentColor: const Color(0xFF8B5E1A),
                            onTap: _addEvolution,
                          ),
                          _ActionTile(
                            label: 'Alta',
                            subtitle: 'Registrar saída',
                            icon: Icons.logout,
                            backgroundColor: const Color(0xFFECFDF5),
                            accentColor: const Color(0xFF0F766E),
                            onTap: _addDischarge,
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
                                  _extensionPeriodText(
                                    detail.extensions.first,
                                  ),
                                ),
                                Text(
                                  'Diárias: ${detail.extensions.first.days}',
                                ),
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
                        title: 'TUSS',
                        count: detail.tussItems.length,
                        children: detail.tussItems
                            .where((item) => item.code.trim().isNotEmpty)
                            .map(
                              (item) => ListTile(
                                dense: true,
                                title: Text('${item.code} • ${item.description}'),
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
                        children: detail.extensions
                            .map(
                              (item) => ListTile(
                                dense: true,
                                title: Text(
                                  _extensionPeriodText(item),
                                ),
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
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: accentColor.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(icon, color: accentColor),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: accentColor,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    subtitle,
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF5B6577),
                    ),
                  ),
                ],
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
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(width: 8),
                CircleAvatar(
                  radius: 12,
                  backgroundColor: const Color(0xFFEEF4FB),
                  child: Text(
                    '$count',
                    style: const TextStyle(
                      fontSize: 12,
                      color: Color(0xFF2D63A6),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            if (children.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 8),
                child: Text('Nenhum registro encontrado.'),
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
  });

  final MobileApi api;
  final int admissionId;
  final String patientName;

  @override
  State<AdmissionEvolutionsPage> createState() => _AdmissionEvolutionsPageState();
}

class _AdmissionEvolutionsPageState extends State<AdmissionEvolutionsPage> {
  List<EvolutionItem> _items = const [];
  bool _loading = true;

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
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final items = await widget.api.listEvolutions(widget.admissionId);
      if (!mounted) return;
      setState(() => _items = items);
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _createEvolution() async {
    final reportController = TextEditingController();
    bool saved = false;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) => Padding(
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
              const Text('Nova evolução',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
              const SizedBox(height: 12),
              TextField(
                controller: reportController,
                maxLines: 8,
                minLines: 6,
                maxLength: 5000,
                decoration: const InputDecoration(
                  labelText: 'Evolução / Relatório',
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: () async {
                  final report = reportController.text.trim();
                  if (report.isEmpty) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Informe a evolução.')),
                    );
                    return;
                  }

                  final item = await widget.api.saveEvolution(
                    admissionId: widget.admissionId,
                    report: report,
                  );

                  if (mounted) {
                    setState(() {
                      _items = [
                        item,
                        ..._items.where((existing) => existing.id != item.id),
                      ];
                    });
                  }

                  saved = true;
                  if (!context.mounted) return;
                  Navigator.of(context).pop();
                },
                child: const Text('Salvar evolução'),
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
        const SnackBar(content: Text('Evolução salva com sucesso.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Evoluções')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createEvolution,
        backgroundColor: const Color(0xFF8B5E1A),
        icon: const Icon(Icons.edit_note),
        label: const Text('Nova evolução'),
      ),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
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
                    Text(
                      'Histórico de evoluções',
                      style: TextStyle(
                        color: Colors.blueGrey.shade700,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
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
                  child: Text('Nenhuma evolução registrada.'),
                ),
              )
            else
              ..._items.map(
                (item) => Card(
                  color: const Color(0xFFFFF8EC),
                  child: Padding(
                    padding: const EdgeInsets.all(16),
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
                        Text(item.report.trim().isEmpty ? '-' : item.report.trim()),
                      ],
                    ),
                  ),
                ),
              ),
            const SizedBox(height: 88),
          ],
        ),
      ),
    );
  }
}
