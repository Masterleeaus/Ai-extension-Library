// To parse this JSON data, do
//
//     final flutterwaveAccountCheacjModel = flutterwaveAccountCheacjModelFromJson(jsonString);

import 'dart:convert';

FlutterwaveAccountCheckModel flutterwaveAccountCheacjModelFromJson(String str) => FlutterwaveAccountCheckModel.fromJson(json.decode(str));

String flutterwaveAccountCheacjModelToJson(FlutterwaveAccountCheckModel data) => json.encode(data.toJson());

class FlutterwaveAccountCheckModel {
  Message message;
  Data data;

  FlutterwaveAccountCheckModel({
    required this.message,
    required this.data,
  });

  factory FlutterwaveAccountCheckModel.fromJson(Map<String, dynamic> json) => FlutterwaveAccountCheckModel(
    message: Message.fromJson(json["message"]),
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message.toJson(),
    "data": data.toJson(),
  };
}

class Data {
  BankInfo bankInfo;

  Data({
    required this.bankInfo,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    bankInfo: BankInfo.fromJson(json["bank_info"]),
  );

  Map<String, dynamic> toJson() => {
    "bank_info": bankInfo.toJson(),
  };
}

class BankInfo {
  dynamic accountName;
  dynamic accountNumber;

  BankInfo({
    required this.accountName,
    required this.accountNumber,
  });

  factory BankInfo.fromJson(Map<String, dynamic> json) => BankInfo(
    accountName: json["account_name"] ?? '',
    accountNumber: json["account_number"] ?? '',
  );

  Map<String, dynamic> toJson() => {
    "account_name": accountName,
    "account_number": accountNumber,
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
