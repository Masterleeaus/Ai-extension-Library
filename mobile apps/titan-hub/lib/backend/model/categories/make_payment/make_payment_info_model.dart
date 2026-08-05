// To parse this JSON data, do
//
//     final makePaymentInfoModel = makePaymentInfoModelFromJson(jsonString);

import 'dart:convert';

MakePaymentInfoModel makePaymentInfoModelFromJson(String str) =>
    MakePaymentInfoModel.fromJson(json.decode(str));

String makePaymentInfoModelToJson(MakePaymentInfoModel data) =>
    json.encode(data.toJson());

class MakePaymentInfoModel {
  Message message;
  Data data;

  MakePaymentInfoModel({
    required this.message,
    required this.data,
  });

  factory MakePaymentInfoModel.fromJson(Map<String, dynamic> json) =>
      MakePaymentInfoModel(
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
  dynamic baseCurrRate;
  GetRemainingFields getRemainingFields;

  MakePaymentcharge makePaymentcharge;
  UserWallet userWallet;
  List<dynamic> transactions;

  Data({
    required this.baseCurr,
    required this.baseCurrRate,
    required this.getRemainingFields,
    required this.makePaymentcharge,
    required this.userWallet,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        baseCurrRate: json["base_curr_rate"]?.toDouble() ?? 0.0,
        getRemainingFields:
            GetRemainingFields.fromJson(json["get_remaining_fields"]),
        makePaymentcharge:
            MakePaymentcharge.fromJson(json["makePaymentcharge"]),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        transactions: List<dynamic>.from(json["transactions"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "base_curr_rate": baseCurrRate,
        "get_remaining_fields": getRemainingFields.toJson(),
        "makePaymentcharge": makePaymentcharge.toJson(),
        "userWallet": userWallet.toJson(),
        "transactions": List<dynamic>.from(transactions.map((x) => x)),
      };
}

class GetRemainingFields {
  String transactionType;
  String attribute;

  GetRemainingFields({
    required this.transactionType,
    required this.attribute,
  });

  factory GetRemainingFields.fromJson(Map<String, dynamic> json) =>
      GetRemainingFields(
        transactionType: json["transaction_type"],
        attribute: json["attribute"],
      );

  Map<String, dynamic> toJson() => {
        "transaction_type": transactionType,
        "attribute": attribute,
      };
}

class MakePaymentcharge {
  dynamic id;
  String slug;
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;
  dynamic dailyLimit;
  dynamic monthlyLimit;

  MakePaymentcharge({
    required this.id,
    required this.slug,
    required this.title,
    required this.fixedCharge,
    required this.percentCharge,
    required this.minLimit,
    required this.maxLimit,
    required this.dailyLimit,
    required this.monthlyLimit,
  });

  factory MakePaymentcharge.fromJson(Map<String, dynamic> json) =>
      MakePaymentcharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"],
        percentCharge: json["percent_charge"],
        minLimit: json["min_limit"],
        maxLimit: json["max_limit"],
        dailyLimit:json["daily_limit"],
        monthlyLimit: json["monthly_limit"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "slug": slug,
        "title": title,
        "fixed_charge": fixedCharge,
        "percent_charge": percentCharge,
        "min_limit": minLimit,
        "max_limit": maxLimit,
        "daily_limit": dailyLimit,
        "monthly_limit": monthlyLimit,
      };
}

class UserWallet {
  dynamic balance;
  String currency;

  UserWallet({
    required this.balance,
    required this.currency,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"],
        currency: json["currency"],
      );

  Map<String, dynamic> toJson() => {
        "balance": balance,
        "currency": currency,
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
