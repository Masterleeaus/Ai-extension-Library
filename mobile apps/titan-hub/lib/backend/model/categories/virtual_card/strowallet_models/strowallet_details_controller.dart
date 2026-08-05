import 'dart:convert';

StrowalletCardDetailsModel strowalletCardDetailsModelFromJson(String str) =>
    StrowalletCardDetailsModel.fromJson(json.decode(str));

String strowalletCardDetailsModelToJson(StrowalletCardDetailsModel data) =>
    json.encode(data.toJson());

class StrowalletCardDetailsModel {
  final Message message;
  final Data data;

  StrowalletCardDetailsModel({
    required this.message,
    required this.data,
  });

  factory StrowalletCardDetailsModel.fromJson(Map<String, dynamic> json) =>
      StrowalletCardDetailsModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  final String baseCurr;
  final MyCards myCards;
  List<BusinessAddress> businessAddress;

  Data({
    required this.baseCurr,
    required this.myCards,
    required this.businessAddress,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        myCards: MyCards.fromJson(json["myCards"]),
        businessAddress: List<BusinessAddress>.from(
            json["business_address"].map((x) => BusinessAddress.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "myCards": myCards.toJson(),
        "business_address":
            List<dynamic>.from(businessAddress.map((x) => x.toJson())),
      };
}

class MyCards {
  final int id;
  final String name;
  final String cardId;
  final String cardBrand;
  final String cardUserId;
  final dynamic expiry;
  final dynamic cvv;
  final String cardType;
  final String city;
  final String state;
  final String zipCode;
  final double amount;
  final String cardBackDetails;
  final String cardBg;
  final String siteTitle;
  final String siteLogo;
  final bool status;

  MyCards({
    required this.id,
    required this.name,
    required this.cardId,
    required this.cardBrand,
    required this.cardUserId,
    this.expiry,
    this.cvv,
    required this.cardType,
    required this.city,
    required this.state,
    required this.zipCode,
    required this.amount,
    required this.cardBackDetails,
    required this.cardBg,
    required this.siteTitle,
    required this.siteLogo,
    required this.status,
  });

  factory MyCards.fromJson(Map<String, dynamic> json) => MyCards(
        id: json["id"],
        name: json["name"],
        cardId: json["card_id"],
        cardBrand: json["card_brand"],
        cardUserId: json["card_user_id"],
        expiry: json["expiry"] ?? '',
        cvv: json["cvv"] ?? '',
        cardType: json["card_type"],
        city: json["city"],
        state: json["state"],
        zipCode: json["zip_code"],
        amount: json["amount"].toDouble(),
        cardBackDetails: json["card_back_details"],
        cardBg: json["card_bg"],
        siteTitle: json["site_title"],
        siteLogo: json["site_logo"],
        status: json["status"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "card_id": cardId,
        "card_brand": cardBrand,
        "card_user_id": cardUserId,
        "expiry": expiry,
        "cvv": cvv,
        "card_type": cardType,
        "city": city,
        "state": state,
        "zip_code": zipCode,
        "amount": amount,
        "card_back_details": cardBackDetails,
        "card_bg": cardBg,
        "site_title": siteTitle,
        "site_logo": siteLogo,
        "status": status,
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

class BusinessAddress {
  int id;
  String labelName;
  String value;

  BusinessAddress({
    required this.id,
    required this.labelName,
    required this.value,
  });

  factory BusinessAddress.fromJson(Map<String, dynamic> json) =>
      BusinessAddress(
        id: json["id"],
        labelName: json["label_name"],
        value: json["value"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "label_name": labelName,
        "value": value,
      };
}
