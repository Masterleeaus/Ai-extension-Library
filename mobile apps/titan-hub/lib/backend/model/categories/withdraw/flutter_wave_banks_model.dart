import 'dart:convert';

FlutterWaveBanksModel flutterWaveBanksModelFromJson(String str) =>
    FlutterWaveBanksModel.fromJson(json.decode(str));

String flutterWaveBanksModelToJson(FlutterWaveBanksModel data) =>
    json.encode(data.toJson());

class FlutterWaveBanksModel {
  final Message message;
  final Data data;

  FlutterWaveBanksModel({
    required this.message,
    required this.data,
  });

  factory FlutterWaveBanksModel.fromJson(Map<String, dynamic> json) =>
      FlutterWaveBanksModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  final List<BankInfos> bankInfo;

  Data({
    required this.bankInfo,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        bankInfo: List<BankInfos>.from(
            json["bank_info"].map((x) => BankInfos.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "bank_info": List<dynamic>.from(bankInfo.map((x) => x.toJson())),
      };
}

class BankInfos {
  final int id;
  final String code;
  final String name;

  BankInfos({
    required this.id,
    required this.code,
    required this.name,
  });

  factory BankInfos.fromJson(Map<String, dynamic> json) => BankInfos(
        id: json["id"],
        code: json["code"],
        name: json["name"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "code": code,
        "name": name,
      };
}

class Message {
  final List<String> success;

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
