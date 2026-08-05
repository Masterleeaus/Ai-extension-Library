import 'dart:convert';

import 'package:qrpay/widgets/dropdown/custom_dropdown_menu.dart';

RemittanceGetRecipientModel remittanceGetRecipientModelFromJson(String str) =>
    RemittanceGetRecipientModel.fromJson(json.decode(str));

String remittanceGetRecipientModelToJson(RemittanceGetRecipientModel data) =>
    json.encode(data.toJson());

class RemittanceGetRecipientModel {
  Message message;
  Data data;

  RemittanceGetRecipientModel({
    required this.message,
    required this.data,
  });

  factory RemittanceGetRecipientModel.fromJson(Map<String, dynamic> json) =>
      RemittanceGetRecipientModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  List<RecipientInfo> recipient;

  Data({
    required this.recipient,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        recipient: List<RecipientInfo>.from(
            json["recipient"].map((x) => RecipientInfo.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "recipient": List<dynamic>.from(recipient.map((x) => x.toJson())),
      };
}

class RecipientInfo implements DropdownMenuModel {
  int id;
  int userId;
  int country;
  String type;
  String alias;
  String firstname;
  String lastname;
  String mobileCode;
  String mobile;
  String city;
  String state;
  String address;
  String zipCode;
  String details;
  DateTime createdAt;
  DateTime updatedAt;

  RecipientInfo({
    required this.id,
    required this.userId,
    required this.country,
    required this.type,
    required this.alias,
    required this.firstname,
    required this.lastname,
    required this.mobileCode,
    required this.mobile,
    required this.city,
    required this.state,
    required this.address,
    required this.zipCode,
    required this.details,
    required this.createdAt,
    required this.updatedAt,
  });

  factory RecipientInfo.fromJson(Map<String, dynamic> json) => RecipientInfo(
        id: json["id"],
        userId: json["user_id"],
        country: json["country"],
        type: json["type"],
        alias: json["alias"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        city: json["city"],
        state: json["state"],
        address: json["address"],
        zipCode: json["zip_code"],
        details: json["details"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "user_id": userId,
        "country": country,
        "type": type,
        "alias": alias,
        "firstname": firstname,
        "lastname": lastname,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "city": city,
        "state": state,
        "address": address,
        "zip_code": zipCode,
        "details": details,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
      };

  @override
  String get title => "$firstname $lastname";
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
