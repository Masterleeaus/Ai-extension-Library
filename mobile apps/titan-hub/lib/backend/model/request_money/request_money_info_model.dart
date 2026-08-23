import 'dart:convert';

RequestMoneyInfoModel requestMoneyInfoModelFromJson(String str) =>
    RequestMoneyInfoModel.fromJson(json.decode(str));

String requestMoneyInfoModelToJson(RequestMoneyInfoModel data) =>
    json.encode(data.toJson());

class RequestMoneyInfoModel {
  Message message;
  Data data;

  RequestMoneyInfoModel({
    required this.message,
    required this.data,
  });

  factory RequestMoneyInfoModel.fromJson(Map<String, dynamic> json) =>
      RequestMoneyInfoModel(
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

  RequestMoneyCharge requestMoneyCharge;
  UserWallet userWallet;
  List<dynamic> transactions;

  Data({
    required this.baseCurr,
    this.baseCurrRate,
    required this.getRemainingFields,
    required this.requestMoneyCharge,
    required this.userWallet,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        baseCurrRate: json["base_curr_rate"]?.toDouble() ?? 0.0,
        getRemainingFields:
            GetRemainingFields.fromJson(json["get_remaining_fields"]),
        requestMoneyCharge:
            RequestMoneyCharge.fromJson(json["requestMoneyCharge"]),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        transactions: List<dynamic>.from(json["transactions"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "base_curr_rate": baseCurrRate,
        "get_remaining_fields": getRemainingFields.toJson(),
        "requestMoneyCharge": requestMoneyCharge.toJson(),
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

class RequestMoneyCharge {
  RequestMoneyCharge({
    required this.id,
    required this.slug,
    required this.title,
    required this.fixedCharge,
    required this.percentCharge,
    required this.minLimit,
    required this.maxLimit,
    required this.monthlyLimit,
    required this.dailyLimit,
  });

  final dynamic id;
  final dynamic slug;
  final dynamic title;
  final dynamic fixedCharge;
  final dynamic percentCharge;
  final dynamic minLimit;
  final dynamic maxLimit;
  final dynamic monthlyLimit;
  final dynamic dailyLimit;

  factory RequestMoneyCharge.fromJson(Map<String, dynamic> json) =>
      RequestMoneyCharge(
        id: json["id"] ?? '',
        slug: json["slug"] ?? '',
        title: json["title"] ?? '',
        fixedCharge: json["fixed_charge"]?.toDouble() ?? 0.0,
        percentCharge: json["percent_charge"]?.toDouble() ?? 0.0,
        minLimit: json["min_limit"]?.toDouble() ?? 0.0,
        maxLimit: json["max_limit"]?.toDouble() ?? 0.0,
        monthlyLimit: json["monthly_limit"]?.toDouble() ?? 0.0,
        dailyLimit: json["daily_limit"]?.toDouble() ?? 0.0,
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "slug": slug,
        "title": title,
        "fixed_charge": fixedCharge,
        "percent_charge": percentCharge,
        "min_limit": minLimit,
        "max_limit": maxLimit,
        "monthly_limit": monthlyLimit,
        "daily_limit": dailyLimit,
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
        balance: json["balance"]?.toDouble() ?? 0.00,
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
