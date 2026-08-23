import 'dart:convert';

UpdateKycModel updateKycModelFromJson(String str) =>
    UpdateKycModel.fromJson(json.decode(str));

String updateKycModelToJson(UpdateKycModel data) => json.encode(data.toJson());

class UpdateKycModel {
  Message message;
  Data data;

  UpdateKycModel({
    required this.message,
    required this.data,
  });

  factory UpdateKycModel.fromJson(Map<String, dynamic> json) => UpdateKycModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String statusInfo;
  int kycStatus;
  List<UserKyc> userKyc;

  Data({
    required this.statusInfo,
    required this.kycStatus,
    required this.userKyc,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        statusInfo: json["status_info"],
        kycStatus: json["kyc_status"],
        userKyc:
            List<UserKyc>.from(json["userKyc"].map((x) => UserKyc.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "status_info": statusInfo,
        "kyc_status": kycStatus,
        "userKyc": List<dynamic>.from(userKyc.map((x) => x.toJson())),
      };
}

class UserKyc {
  String type;
  String label;
  String name;
  bool required;
  Validation validation;

  UserKyc({
    required this.type,
    required this.label,
    required this.name,
    required this.required,
    required this.validation,
  });

  factory UserKyc.fromJson(Map<String, dynamic> json) => UserKyc(
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
  String max;
  List<String> mimes;
  int min;
  List<dynamic> options;
  bool required;

  Validation({
    required this.max,
    required this.mimes,
    required this.min,
    required this.options,
    required this.required,
  });

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
  List<String> success;

  Message({
    required this.success,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
