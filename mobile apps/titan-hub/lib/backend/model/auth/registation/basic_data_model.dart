class BasicDataModel {
  BasicDataModel({
    required this.message,
    required this.data,   
  });

  Message message;
  Data data;

  factory BasicDataModel.fromJson(Map<String, dynamic> json) => BasicDataModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  Data({
    required this.emailVerification,
    required this.kycVerification,
    required this.mobileCode,
    required this.registerKycFields,
    required this.countries,
  });
  
  bool emailVerification;
  bool kycVerification;
  dynamic mobileCode;
  RegisterKycFields? registerKycFields; // Make it nullable
  List<Country> countries;

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        emailVerification: json["email_verification"],
        kycVerification: json["kyc_verification"],
        mobileCode: json["mobile_code"],
        registerKycFields: json["register_kyc_fields"] != null
            ? RegisterKycFields.fromJson(json["register_kyc_fields"])
            : null, // Handle null case
        countries: List<Country>.from(
            json["countries"].map((x) => Country.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "email_verification": emailVerification,
        "kyc_verification": kycVerification,
        "mobile_code": mobileCode,
        "register_kyc_fields": registerKycFields?.toJson(), // Handle null case
        "countries": List<dynamic>.from(countries.map((x) => x.toJson())),
      };
}

class Country {
  Country({
    required this.id,
    required this.name,
    required this.mobileCode,
    required this.currencyName,
    required this.currencyCode,
    required this.currencySymbol,
    required this.iso2,
  });

  dynamic id;
  dynamic name;
  dynamic mobileCode;
  dynamic currencyName;
  dynamic currencyCode;
  dynamic currencySymbol;
  dynamic iso2;

  factory Country.fromJson(Map<String, dynamic> json) => Country(
        id: json["id"],
        name: json["name"],
        mobileCode: json["mobile_code"],
        currencyName: json["currency_name"],
        currencyCode: json["currency_code"],
        currencySymbol: json["currency_symbol"],
        iso2: json["iso2"],
      );

Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "mobile_code": mobileCode,
        "currency_name": currencyName,
        "currency_code": currencyCode,
        "currency_symbol": currencySymbol,
        "iso2": iso2,
      };
}


class RegisterKycFields {
  RegisterKycFields({
    required this.id,
    required this.slug,
    required this.userType,
    required this.fields,
    required this.status,
    required this.lastEditBy,
    required this.createdAt,
    required this.updatedAt,
  });

  dynamic id;
  dynamic slug;
  dynamic userType;
  List<Field> fields;
  dynamic status;
  dynamic lastEditBy;
  DateTime createdAt;
  DateTime updatedAt;

  factory RegisterKycFields.fromJson(Map<String, dynamic> json) =>
      RegisterKycFields(
        id: json["id"],
        slug: json["slug"],
        userType: json["user_type"],
        fields: List<Field>.from(json["fields"].map((x) => Field.fromJson(x))),
        status: json["status"],
        lastEditBy: json["last_edit_by"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "slug": slug,
        "user_type": userType,
        "fields": List<dynamic>.from(fields.map((x) => x.toJson())),
        "status": status,
        "last_edit_by": lastEditBy,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
      };
}

class Field {
  Field({
    required this.type,
    required this.label,
    required this.name,
    required this.required,
    required this.validation,
  });

  dynamic type;
  dynamic label;
  dynamic name;
  bool required;
  Validation validation;

  factory Field.fromJson(Map<String, dynamic> json) => Field(
        type: json["type"],
        label: json["label"],
        name: json["name"],
        required: json["required"],
        validation: Validation.fromJson(json["validation"]),
      );

  Map<String, dynamic> toJson() => {
        "type": type,
        "label": label,
        "name": name,
        "required": required,
        "validation": validation.toJson(),
      };
}

class Validation {
  Validation({
    required this.max,
    required this.mimes,
    required this.min,
    required this.options,
    required this.required,
  });

  dynamic max;
  List<String> mimes;
  dynamic min;
  List<dynamic> options;
  bool required;

  factory Validation.fromJson(Map<String, dynamic> json) => Validation(
        max: json["max"],
        mimes: List<String>.from(json["mimes"].map((x) => x)),
        min: json["min"],
        options: List<dynamic>.from(json["options"].map((x) => x)),
        required: json["required"],
      );

  Map<String, dynamic> toJson() => {
        "max": max,
        "mimes": List<dynamic>.from(mimes.map((x) => x)),
        "min": min,
        "options": List<dynamic>.from(options.map((x) => x)),
        "required": required,
      };
}

class Message {
  Message({
    required this.success,
  });

  List<String> success;

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
