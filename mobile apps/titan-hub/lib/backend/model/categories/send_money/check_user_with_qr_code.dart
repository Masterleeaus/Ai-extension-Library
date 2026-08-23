import 'dart:convert';

CheckUserWithQrCodeModel checkUserWithQrCodeModelFromJson(String str) =>
    CheckUserWithQrCodeModel.fromJson(json.decode(str));

String checkUserWithQrCodeModelToJson(CheckUserWithQrCodeModel data) =>
    json.encode(data.toJson());

class CheckUserWithQrCodeModel {
  CheckUserWithQrCodeModel({
    required this.message,
    required this.data,
  });

  final Message message;
  final Data data;

  factory CheckUserWithQrCodeModel.fromJson(Map<String, dynamic> json) =>
      CheckUserWithQrCodeModel(
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
    required this.userMobile,
  });

  final String userMobile;

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        userMobile: json["user_email"],
      );

  Map<String, dynamic> toJson() => {
        "user_email": userMobile,
      };
}

class Message {
  Message({
    required this.success,
  });

  final List<String> success;

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
