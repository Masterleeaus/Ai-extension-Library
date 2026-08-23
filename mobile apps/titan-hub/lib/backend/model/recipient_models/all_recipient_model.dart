// To parse this JSON data, do
//
//     final allRecipientModel = allRecipientModelFromJson(jsonString);

import 'dart:convert';

AllRecipientModel allRecipientModelFromJson(String str) =>
    AllRecipientModel.fromJson(json.decode(str));

String allRecipientModelToJson(AllRecipientModel data) =>
    json.encode(data.toJson());

class AllRecipientModel {
  AllRecipientModel({
    required this.message,
    required this.data,
  });

  Message message;
  Data data;

  factory AllRecipientModel.fromJson(Map<String, dynamic> json) =>
      AllRecipientModel(
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
    required this.recipients,
    required this.countryFlugPath,
    required this.receiverCountries,
  });

  List<Recipient> recipients;
  String countryFlugPath;
  List<ReceiverCountry> receiverCountries;

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        recipients: List<Recipient>.from(
            json["recipients"].map((x) => Recipient.fromJson(x))),
        countryFlugPath: json["countryFlugPath"],
        receiverCountries: List<ReceiverCountry>.from(
            json["receiverCountries"].map((x) => ReceiverCountry.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "recipients": List<dynamic>.from(recipients.map((x) => x.toJson())),
        "countryFlugPath": countryFlugPath,
        "receiverCountries":
            List<dynamic>.from(receiverCountries.map((x) => x.toJson())),
      };
}

class ReceiverCountry {
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
}

class Recipient {
  Recipient({
    required this.id,
    required this.country,
    required this.countryName,
    required this.trxType,
    required this.trxTypeName,
    required this.alias,
    required this.firstname,
    required this.lastname,
    required this.mobileCode,
    required this.mobile,
    required this.city,
    required this.state,
    required this.address,
    required this.zipCode,
    required this.createdAt,
    required this.updatedAt,
  });

  int id;
  int country;
  String countryName;
  String trxType;
  String trxTypeName;
  String alias;
  String firstname;
  String lastname;
  String mobileCode;
  String mobile;
  String city;
  String state;
  String address;
  String zipCode;
  DateTime createdAt;
  DateTime updatedAt;

  factory Recipient.fromJson(Map<String, dynamic> json) => Recipient(
        id: json["id"],
        country: json["country"],
        countryName: json["country_name"],
        trxType: json["trx_type"],
        trxTypeName: json["trx_type_name"],
        alias: json["alias"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        city: json["city"],
        state: json["state"],
        address: json["address"],
        zipCode: json["zip_code"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "country": country,
        "country_name": countryName,
        "trx_type": trxType,
        "trx_type_name": trxTypeName,
        "alias": alias,
        "firstname": firstname,
        "lastname": lastname,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "city": city,
        "state": state,
        "address": address,
        "zip_code": zipCode,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
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
