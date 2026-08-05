import 'dart:convert';

CheckUserQrCodeModel checkUserQrCodeModelFromJson(String str) =>
    CheckUserQrCodeModel.fromJson(json.decode(str));

String checkUserQrCodeModelToJson(CheckUserQrCodeModel data) =>
    json.encode(data.toJson());

class CheckUserQrCodeModel {
  Message message;
  Data data;

  CheckUserQrCodeModel({
    required this.message,
    required this.data,
  });

  factory CheckUserQrCodeModel.fromJson(Map<String, dynamic> json) =>
      CheckUserQrCodeModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String userEmail;

  Data({
    required this.userEmail,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        userEmail: json["user_email"],
      );

  Map<String, dynamic> toJson() => {
        "user_email": userEmail,
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
