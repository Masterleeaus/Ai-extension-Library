// To parse this JSON data, do
//
//     final checkMercantWithQrCodeModel = checkMercantWithQrCodeModelFromJson(jsonString);

import 'dart:convert';

CheckMercantWithQrCodeModel checkMercantWithQrCodeModelFromJson(String str) =>
    CheckMercantWithQrCodeModel.fromJson(json.decode(str));

String checkMercantWithQrCodeModelToJson(CheckMercantWithQrCodeModel data) =>
    json.encode(data.toJson());

class CheckMercantWithQrCodeModel {
  Message message;
  Data data;

  CheckMercantWithQrCodeModel({
    required this.message,
    required this.data,
  });

  factory CheckMercantWithQrCodeModel.fromJson(Map<String, dynamic> json) =>
      CheckMercantWithQrCodeModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String merchantMobile;

  Data({
    required this.merchantMobile,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        merchantMobile: json["merchant_email"],
      );

  Map<String, dynamic> toJson() => {
        "merchant_email": merchantMobile,
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
