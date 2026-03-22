int _asInt(dynamic value) {
  if (value is int) return value;
  if (value is double) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

String _cleanDate(dynamic value) {
  final text = (value as String? ?? '').trim();
  if (text.isEmpty || text == '0000-00-00' || text == '0000-00-00 00:00:00') {
    return '';
  }
  return text;
}

class SessionUser {
  const SessionUser({
    required this.id,
    required this.name,
    required this.email,
    required this.roleLevel,
    required this.roleName,
    required this.token,
  });

  final int id;
  final String name;
  final String email;
  final int roleLevel;
  final String roleName;
  final String token;
}

class AdmissionItem {
  const AdmissionItem({
    required this.id,
    required this.patientName,
    required this.insuranceName,
    required this.hospitalName,
    required this.cidCode,
    required this.authorizationCode,
    required this.evolutionReport,
    required this.admissionDate,
    required this.dischargeDate,
    required this.dischargeType,
  });

  factory AdmissionItem.fromJson(Map<String, dynamic> json) {
    return AdmissionItem(
      id: _asInt(json['id']),
      patientName: json['patient_name'] as String? ?? '-',
      insuranceName: json['insurance_name'] as String? ?? '',
      hospitalName: json['hospital_name'] as String? ?? '',
      cidCode: json['cid_code'] as String? ?? '',
      authorizationCode: json['authorization_code'] as String? ?? '',
      evolutionReport: json['evolution_report'] as String? ?? '',
      admissionDate: _cleanDate(json['admission_date']),
      dischargeDate: _cleanDate(json['discharge_date']),
      dischargeType: json['discharge_type'] as String? ?? '',
    );
  }

  final int id;
  final String patientName;
  final String insuranceName;
  final String hospitalName;
  final String cidCode;
  final String authorizationCode;
  final String evolutionReport;
  final String admissionDate;
  final String dischargeDate;
  final String dischargeType;
}

class TussItem {
  const TussItem({
    required this.id,
    required this.code,
    required this.description,
    required this.requestedQuantity,
    required this.releasedQuantity,
    required this.releasedFlag,
    required this.performedAt,
    required this.releasedAt,
    required this.releasedBy,
  });

  factory TussItem.fromJson(Map<String, dynamic> json) {
    return TussItem(
      id: _asInt(json['id']),
      code: json['code'] as String? ?? '',
      description: json['description'] as String? ?? '',
      requestedQuantity: _asInt(json['requested_quantity']),
      releasedQuantity: _asInt(json['released_quantity']),
      releasedFlag: json['released_flag'] as String? ?? '',
      performedAt: _cleanDate(json['performed_at']),
      releasedAt: _cleanDate(json['released_at']),
      releasedBy: json['released_by'] as String? ?? '',
    );
  }

  final int id;
  final String code;
  final String description;
  final int requestedQuantity;
  final int releasedQuantity;
  final String releasedFlag;
  final String performedAt;
  final String releasedAt;
  final String releasedBy;
}

class ExtensionItem {
  const ExtensionItem({
    required this.id,
    required this.accommodation,
    required this.startDate,
    required this.endDate,
    required this.days,
  });

  factory ExtensionItem.fromJson(Map<String, dynamic> json) {
    return ExtensionItem(
      id: _asInt(json['id']),
      accommodation: json['accommodation'] as String? ?? '',
      startDate: _cleanDate(json['start_date']),
      endDate: _cleanDate(json['end_date']),
      days: _asInt(json['days']),
    );
  }

  final int id;
  final String accommodation;
  final String startDate;
  final String endDate;
  final int days;
}

class AdmissionDetail {
  const AdmissionDetail({
    required this.admission,
    required this.tussItems,
    required this.extensions,
  });

  final AdmissionItem admission;
  final List<TussItem> tussItems;
  final List<ExtensionItem> extensions;
}

class TussCatalogItem {
  const TussCatalogItem({
    required this.code,
    required this.description,
  });

  factory TussCatalogItem.fromJson(Map<String, dynamic> json) {
    return TussCatalogItem(
      code: json['code'] as String? ?? '',
      description: json['description'] as String? ?? '',
    );
  }

  final String code;
  final String description;
}

class EvolutionItem {
  const EvolutionItem({
    required this.id,
    required this.report,
    required this.visitedAt,
    required this.createdBy,
    required this.visitNumber,
  });

  factory EvolutionItem.fromJson(Map<String, dynamic> json) {
    return EvolutionItem(
      id: _asInt(json['id']),
      report: json['report'] as String? ?? '',
      visitedAt: _cleanDate(json['visited_at']),
      createdBy: json['created_by'] as String? ?? '',
      visitNumber: _asInt(json['visit_number']),
    );
  }

  final int id;
  final String report;
  final String visitedAt;
  final String createdBy;
  final int visitNumber;
}
