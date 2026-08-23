// To parse this JSON data, do
//
//     final recipientEditModel = recipientEditModelFromJson(jsonString);

import 'dart:convert';

RecipientEditModel recipientEditModelFromJson(String str) =>
    RecipientEditModel.fromJson(json.decode(str));

String recipientEditModelToJson(RecipientEditModel data) =>
    json.encode(data.toJson());

class RecipientEditModel {
  Message message;
  Data data;

  RecipientEditModel({
    required this.message,
    required this.data,
  });

  factory RecipientEditModel.fromJson(Map<String, dynamic> json) =>
      RecipientEditModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  Recipient recipient;
  String baseCurr;
  String countryFlugPath;
  String defaultImage;
  List<TransactionType> transactionTypes;
  List<ReceiverCountry> receiverCountries;
  List<Bank> banks;
  List<Bank> cashPickupsPoints;

  Data({
    required this.recipient,
    required this.baseCurr,
    required this.countryFlugPath,
    required this.defaultImage,
    required this.transactionTypes,
    required this.receiverCountries,
    required this.banks,
    required this.cashPickupsPoints,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        recipient: Recipient.fromJson(json["recipient"]),
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
        "recipient": recipient.toJson(),
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

class Bank {
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
}

class ReceiverCountry {
  int id;
  String country;
  String name;
  String code;
  String mobileCode;
  String symbol;
  String flag;
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
    required this.flag,
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
        flag: json["flag"],
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
}

class Recipient {
  int id;
  int country;
  String type;
  String alias;
  String firstname;
  String lastname;
  String mobileCode;
  String mobile;
  String city;
  String address;
  String state;
  String zipCode;
  String accountNumber;
  String email;
  DateTime createdAt;
  DateTime updatedAt;

  Recipient({
    required this.id,
    required this.country,
    required this.type,
    required this.alias,
    required this.firstname,
    required this.lastname,
    required this.mobileCode,
    required this.mobile,
    required this.city,
    required this.address,
    required this.state,
    required this.zipCode,
    required this.createdAt,
    required this.updatedAt,
    required this.accountNumber,
    required this.email,
  });

  factory Recipient.fromJson(Map<String, dynamic> json) => Recipient(
        id: json["id"],
        country: json["country"],
        type: json["type"],
        alias: json["alias"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        city: json["city"],
        address: json["address"],
        state: json["state"],
        zipCode: json["zip_code"],
        accountNumber: json["account_number"],
        email: json["email"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "country": country,
        "type": type,
        "alias": alias,
        "firstname": firstname,
        "lastname": lastname,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "city": city,
        "address": address,
        "state": state,
        "zip_code": zipCode,
        "account_number": accountNumber,
        "email": email,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
      };
}

class TransactionType {
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
