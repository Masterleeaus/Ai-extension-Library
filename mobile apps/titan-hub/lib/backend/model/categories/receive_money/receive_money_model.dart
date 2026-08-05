import 'dart:convert';

ReceiveMoneyModel receiveMoneyModelFromJson(String str) =>
    ReceiveMoneyModel.fromJson(json.decode(str));

String receiveMoneyModelToJson(ReceiveMoneyModel data) =>
    json.encode(data.toJson());

class ReceiveMoneyModel {
  ReceiveMoneyModel({
    required this.message,
    required this.data,
  });

  final Message message;
  final Data data;

  factory ReceiveMoneyModel.fromJson(Map<String, dynamic> json) =>
      ReceiveMoneyModel(
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
    required this.uniqueCode,
    required this.qrCode,
  });

  final String uniqueCode;
  final dynamic qrCode;

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        uniqueCode: json["uniqueCode"],
        qrCode: json["qrCode"] ?? "",
      );

  Map<String, dynamic> toJson() => {
        "uniqueCode": uniqueCode,
        "qrCode": qrCode,
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
