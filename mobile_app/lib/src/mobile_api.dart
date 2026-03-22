import 'dart:convert';

import 'package:http/http.dart' as http;
import 'package:mobile_app/src/models.dart';
import 'package:shared_preferences/shared_preferences.dart';

class MobileApi {
  MobileApi();

  static const String _baseUrl =
      'http://10.0.2.2:8090/FullCare/api/mobile/index.php';
  static const String _tokenKey = 'fullcare_mobile_token';

  String? _token;

  Future<void> loadSavedToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
  }

  Future<void> clearSession() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }

  bool get hasToken => _token != null && _token!.isNotEmpty;

  Future<SessionUser> login({
    required String email,
    required String password,
  }) async {
    final payload = await _request(
      method: 'POST',
      action: 'login',
      body: {
        'email': email,
        'password': password,
      },
    );

    final data = payload['data'] as Map<String, dynamic>;
    final user = data['user'] as Map<String, dynamic>;
    final token = data['token'] as String;
    _token = token;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);

    return SessionUser(
      id: user['id'] as int? ?? 0,
      name: user['name'] as String? ?? '',
      email: user['email'] as String? ?? '',
      roleLevel: user['role_level'] as int? ?? 99,
      roleName: user['role_name'] as String? ?? '',
      token: token,
    );
  }

  Future<SessionUser> me() async {
    final payload = await _request(action: 'me');
    final data = payload['data'] as Map<String, dynamic>;
    return SessionUser(
      id: data['id'] as int? ?? 0,
      name: data['name'] as String? ?? '',
      email: data['email'] as String? ?? '',
      roleLevel: data['role_level'] as int? ?? 99,
      roleName: data['role_name'] as String? ?? '',
      token: _token ?? '',
    );
  }

  Future<List<AdmissionItem>> listAdmissions(String query) async {
    final payload =
        await _request(action: 'admissions', query: {'query': query});
    final items = (payload['data'] as Map<String, dynamic>)['items'] as List<dynamic>? ?? [];
    return items
        .map((item) => AdmissionItem.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<AdmissionDetail> fetchAdmissionDetail(int admissionId) async {
    final payload =
        await _request(action: 'admission', query: {'id': '$admissionId'});
    final data = payload['data'] as Map<String, dynamic>;
    return AdmissionDetail(
      admission:
          AdmissionItem.fromJson(data['admission'] as Map<String, dynamic>),
      tussItems: ((data['tuss_items'] as List<dynamic>? ?? []))
          .map((item) => TussItem.fromJson(item as Map<String, dynamic>))
          .toList(),
      extensions: ((data['extensions'] as List<dynamic>? ?? []))
          .map((item) => ExtensionItem.fromJson(item as Map<String, dynamic>))
          .toList(),
    );
  }

  Future<List<TussCatalogItem>> searchTussCatalog(String query) async {
    final payload =
        await _request(action: 'tuss-catalog', query: {'query': query});
    final items = (payload['data'] as Map<String, dynamic>)['items'] as List<dynamic>? ?? [];
    return items
        .map((item) => TussCatalogItem.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<List<String>> listDischargeTypes() async {
    final payload = await _request(action: 'discharge-types');
    final items =
        (payload['data'] as Map<String, dynamic>)['items'] as List<dynamic>? ??
            [];
    return items
        .map((item) => item?.toString().trim() ?? '')
        .where((item) => item.isNotEmpty)
        .toList();
  }

  Future<TussItem> createTuss({
    required int admissionId,
    required String code,
    required int requestedQuantity,
    required int releasedQuantity,
    required String releasedFlag,
    String performedAt = '',
  }) async {
    final payload = await _request(
      method: 'POST',
      action: 'admission-tuss',
      body: {
        'admission_id': admissionId,
        'code': code,
        'requested_quantity': requestedQuantity,
        'released_quantity': releasedQuantity,
        'released_flag': releasedFlag,
        'performed_at': performedAt,
      },
    );

    return TussItem.fromJson(payload['data'] as Map<String, dynamic>);
  }

  Future<ExtensionItem> createExtension({
    required int admissionId,
    required String accommodation,
    required int days,
    required String startDate,
    required String endDate,
    String isolationFlag = 'n',
  }) async {
    final payload = await _request(
      method: 'POST',
      action: 'admission-extension',
      body: {
        'admission_id': admissionId,
        'accommodation': accommodation,
        'days': days,
        'start_date': startDate,
        'end_date': endDate,
        'isolation_flag': isolationFlag,
      },
    );

    return ExtensionItem.fromJson(payload['data'] as Map<String, dynamic>);
  }

  Future<void> createDischarge({
    required int admissionId,
    required String type,
    required String date,
    required String time,
  }) async {
    await _request(
      method: 'POST',
      action: 'admission-discharge',
      body: {
        'admission_id': admissionId,
        'type': type,
        'date': date,
        'time': time,
      },
    );
  }

  Future<List<EvolutionItem>> listEvolutions(int admissionId) async {
    final payload = await _request(
      action: 'admission-evolutions',
      query: {'id': '$admissionId'},
    );
    final items =
        (payload['data'] as Map<String, dynamic>)['items'] as List<dynamic>? ??
            [];
    return items
        .map((item) => EvolutionItem.fromJson(item as Map<String, dynamic>))
        .toList();
  }

  Future<EvolutionItem> saveEvolution({
    required int admissionId,
    required String report,
  }) async {
    final payload = await _request(
      method: 'POST',
      action: 'admission-evolution',
      body: {
        'admission_id': admissionId,
        'report': report,
      },
    );

    final data = payload['data'] as Map<String, dynamic>;
    return EvolutionItem.fromJson(data);
  }

  Future<Map<String, dynamic>> _request({
    String method = 'GET',
    required String action,
    Map<String, String>? query,
    Map<String, dynamic>? body,
  }) async {
    final uri = Uri.parse(_baseUrl).replace(
      queryParameters: {
        'action': action,
        ...?query,
      },
    );

    final headers = <String, String>{
      'Content-Type': 'application/json',
    };
    if (_token != null && _token!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $_token';
    }

    late final http.Response response;
    if (method == 'POST') {
      response = await http.post(
        uri,
        headers: headers,
        body: jsonEncode(body ?? <String, dynamic>{}),
      );
    } else {
      response = await http.get(uri, headers: headers);
    }

    final decoded = jsonDecode(response.body) as Map<String, dynamic>;
    if (response.statusCode == 401) {
      await clearSession();
      throw Exception(decoded['message'] ?? 'Sessao expirada.');
    }
    if (response.statusCode >= 400 || decoded['success'] != true) {
      throw Exception(decoded['message'] ?? 'Falha na requisicao.');
    }

    return decoded;
  }
}
