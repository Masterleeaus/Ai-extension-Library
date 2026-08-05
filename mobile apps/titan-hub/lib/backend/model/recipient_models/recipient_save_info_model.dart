import 'dart:convert';

import 'package:qrpay/widgets/dropdown/custom_dropdown_menu.dart';

RecipientSaveInfoModel recipientSaveInfoModelFromJson(String str) =>
    RecipientSaveInfoModel.fromJson(json.decode(str));

String recipientSaveInfoModelToJson(RecipientSaveInfoModel data) =>
    json.encode(data.toJson());

class RecipientSaveInfoModel {
  Message message;
  Data data;

  RecipientSaveInfoModel({
    required this.message,
    required this.data,
  });

  factory RecipientSaveInfoModel.fromJson(Map<String, dynamic> json) =>
      RecipientSaveInfoModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String baseCurr;
  String countryFlugPath;
  String defaultImage;
  List<TransactionType> transactionTypes;
  List<ReceiverCountry> receiverCountries;
  List<Bank> banks;
  List<Bank> cashPickupsPoints;

  Data({
    required this.baseCurr,
    required this.countryFlugPath,
    required this.defaultImage,
    required this.transactionTypes,
    required this.receiverCountries,
    required this.banks,
    required this.cashPickupsPoints,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        countryFlugPath: json["countryFlugPath"],
        defaultImage: json["default_image"],
        transactionTypes: List<TransactionType>.from(
            json["transactionTypes"].map((x) => TransactionType.fromJson(x))),
        receiverCountries: List<ReceiverCountry>.from(
            json["receiverCountries"].map((x) => ReceiverCountry.fromJson(x))),
        banks: List<Bank>.from(json["banks"].map((x) => Bank.fromJson(x))),
        cashPickupsPoints: List<Bank>.from(
            json["cashPickupsPoints"].map((x) => Bank.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "countryFlugPath": countryFlugPath,
        "default_image": defaultImage,
        "transactionTypes":
            List<dynamic>.from(transactionTypes.map((x) => x.toJson())),
        "receiverCountries":
            List<dynamic>.from(receiverCountries.map((x) => x.toJson())),
        "banks": List<dynamic>.from(banks.map((x) => x.toJson())),
        "cashPickupsPoints":
            List<dynamic>.from(cashPickupsPoints.map((x) => x.toJson())),
      };
}

class Bank implements DropdownMenuModel {
  int id;
  int adminId;
  String name;
  String alias;
  int status;
  DateTime createdAt;
  DateTime updatedAt;
  String editData;

  Bank({
    required this.id,
    required this.adminId,
    required this.name,
    required this.alias,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    required this.editData,
  });

  factory Bank.fromJson(Map<String, dynamic> json) => Bank(
        id: json["id"],
        adminId: json["admin_id"],
        name: json["name"],
        alias: json["alias"],
        status: json["status"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        editData: json["editData"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "admin_id": adminId,
        "name": name,
        "alias": alias,
        "status": status,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "editData": editData,
      };

  @override
  String get title => name;
}

class ReceiverCountry implements DropdownMenuModel {
  int id;
  String country;
  String name;
  String code;
  String mobileCode;
  String symbol;
  dynamic flag;
  double rate;
  int status;
  DateTime createdAt;
  DateTime updatedAt;

  ReceiverCountry({
    required this.id,
    required this.country,
    required this.name,
    required this.code,
    required this.mobileCode,
    required this.symbol,
    this.flag,
    required this.rate,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
  });

  factory ReceiverCountry.fromJson(Map<String, dynamic> json) =>
      ReceiverCountry(
        id: json["id"],
        country: json["country"],
        name: json["name"],
        code: json["code"],
        mobileCode: json["mobile_code"],
        symbol: json["symbol"],
        flag: json["flag"] ?? '',
        rate: json["rate"]?.toDouble(),
        status: json["status"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "country": country,
        "name": name,
        "code": code,
        "mobile_code": mobileCode,
        "symbol": symbol,
        "flag": flag,
        "rate": rate,
        "status": status,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
      };

  @override
  String get title => country;
}

class TransactionType implements DropdownMenuModel {
  int id;
  String fieldName;
  String labelName;

  TransactionType({
    required this.id,
    required this.fieldName,
    required this.labelName,
  });

  factory TransactionType.fromJson(Map<String, dynamic> json) =>
      TransactionType(
        id: json["id"],
        fieldName: json["field_name"],
        labelName: json["label_name"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "field_name": fieldName,
        "label_name": labelName,
      };

  @override
  String get title => labelName;
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
