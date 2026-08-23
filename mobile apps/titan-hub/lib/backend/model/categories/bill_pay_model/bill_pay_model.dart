// To parse this JSON data, do
//
//     final billPayInfoModel = billPayInfoModelFromJson(jsonString);

import 'dart:convert';

import 'package:qrpay/widgets/dropdown/custom_dropdown_menu.dart';

BillPayInfoModel billPayInfoModelFromJson(String str) =>
    BillPayInfoModel.fromJson(json.decode(str));

String billPayInfoModelToJson(BillPayInfoModel data) =>
    json.encode(data.toJson());

class BillPayInfoModel {
  Message message;
  Data data;

  BillPayInfoModel({
    required this.message,
    required this.data,
  });

  factory BillPayInfoModel.fromJson(Map<String, dynamic> json) =>
      BillPayInfoModel(
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
  BillPayCharge billPayCharge;
  UserWallet userWallet;
  List<BillType> billTypes;
  final List<BillMonth> billMonths;
  List<Transaction> transactions;

  Data({
    required this.baseCurr,
    required this.baseCurrRate,
    required this.getRemainingFields,
    required this.billPayCharge,
    required this.userWallet,
    required this.billTypes,
    required this.billMonths,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        baseCurrRate: json["base_curr_rate"]?.toDouble() ?? 0.0,
        getRemainingFields:
            GetRemainingFields.fromJson(json["get_remaining_fields"]),
        billPayCharge: BillPayCharge.fromJson(json["billPayCharge"]),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        billTypes: List<BillType>.from(
            json["billTypes"].map((x) => BillType.fromJson(x))),
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
        billMonths: List<BillMonth>.from(
            json["bill_months"].map((x) => BillMonth.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "base_curr_rate": baseCurrRate,
        "get_remaining_fields": getRemainingFields.toJson(),
        "billPayCharge": billPayCharge.toJson(),
        "userWallet": userWallet.toJson(),
        "billTypes": List<dynamic>.from(billTypes.map((x) => x.toJson())),
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
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

class BillPayCharge {
  int id;
  String slug;
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;
  dynamic monthlyLimit;
  dynamic dailyLimit;

  BillPayCharge({
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

  factory BillPayCharge.fromJson(Map<String, dynamic> json) => BillPayCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
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

class Transaction {
  int id;
  String trx;
  String transactionType;
  String requestAmount;
  String payable;
  String billType;
  String billNumber;
  String totalCharge;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;

  Transaction({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.billType,
    required this.billNumber,
    required this.totalCharge,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        trx: json["trx"],
        transactionType: json["transaction_type"],
        requestAmount: json["request_amount"],
        payable: json["payable"],
        billType: json["bill_type"],
        billNumber: json["bill_number"],
        totalCharge: json["total_charge"],
        currentBalance: json["current_balance"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "bill_type": billType,
        "bill_number": billNumber,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
      };
}

class StatusInfo {
  int success;
  int pending;
  int rejected;

  StatusInfo({
    required this.success,
    required this.pending,
    required this.rejected,
  });

  factory StatusInfo.fromJson(Map<String, dynamic> json) => StatusInfo(
        success: json["success"],
        pending: json["pending"],
        rejected: json["rejected"],
      );

  Map<String, dynamic> toJson() => {
        "success": success,
        "pending": pending,
        "rejected": rejected,
      };
}

class UserWallet {
  double balance;
  String currency;
  // dynamic rate;

  UserWallet({
    required this.balance,
    required this.currency,
    // required this.rate,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"]?.toDouble(),
        currency: json["currency"],
        // rate: json["rate"]?.toDouble(),
      );

  Map<String, dynamic> toJson() => {
        "balance": balance,
        "currency": currency,
        // "rate": rate,
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

class BillType implements DropdownMenuModel {
  final int id;
  final String? name;
  final String? countryCode;
  final String? countryName;
  final String? type;
  final String? serviceType;

  final dynamic minLocalTransactionAmount;
  final dynamic maxLocalTransactionAmount;
  final double? localTransactionFee;
  final String? localTransactionFeeCurrencyCode;
  final int? localTransactionFeePercentage;
  final String? denominationType;
  final String? itemType;
  final dynamic slug;
  final dynamic receiverCurrencyRate;
  final dynamic receiverCurrencyCode;

  BillType({
    required this.id,
    this.name,
    this.countryCode,
    this.countryName,
    this.type,
    this.serviceType,
    this.minLocalTransactionAmount,
    this.maxLocalTransactionAmount,
    this.localTransactionFee,
    this.localTransactionFeeCurrencyCode,
    this.localTransactionFeePercentage,
    this.denominationType,
    this.itemType,
    this.slug,
    required this.receiverCurrencyRate,
    required this.receiverCurrencyCode,
  });

  factory BillType.fromJson(Map<String, dynamic> json) => BillType(
        id: json["id"],
        name: json["name"],
        countryCode: json["countryCode"],
        countryName: json["countryName"],
        type: json["type"],
        serviceType: json["serviceType"],
        minLocalTransactionAmount:
            json["minLocalTransactionAmount"]?.toDouble() ?? 0.0,
        maxLocalTransactionAmount:
            json["maxLocalTransactionAmount"]?.toDouble() ?? 0.0,
        localTransactionFee: json["localTransactionFee"]?.toDouble() ?? 0.0,
        localTransactionFeeCurrencyCode:
            json["localTransactionFeeCurrencyCode"],
        localTransactionFeePercentage: json["localTransactionFeePercentage"],
        denominationType: json["denominationType"],
        itemType: json["item_type"],
        slug: json["slug"] ?? "",
        receiverCurrencyRate: json["receiver_currency_rate"]?.toDouble() ?? 0.0,
        receiverCurrencyCode: json["receiver_currency_code"] ?? "",
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "countryCode": countryCode,
        "countryName": countryName,
        "type": type,
        "serviceType": serviceType,
        "minLocalTransactionAmount": minLocalTransactionAmount,
        "maxLocalTransactionAmount": maxLocalTransactionAmount,
        "localTransactionFee": localTransactionFee,
        "localTransactionFeeCurrencyCode": localTransactionFeeCurrencyCode,
        "localTransactionFeePercentage": localTransactionFeePercentage,
        "denominationType": denominationType,
        "item_type": itemType,
        "slug": slug,
        "receiver_currency_rate": receiverCurrencyRate,
        "receiver_currency_code": receiverCurrencyCode,
      };

  @override
  String get title => name!;
}

class Fx {
  final double rate;
  final String currencyCode;

  Fx({
    required this.rate,
    required this.currencyCode,
  });

  factory Fx.fromJson(Map<String, dynamic> json) => Fx(
        rate: json["rate"]?.toDouble(),
        currencyCode: json["currencyCode"],
      );

  Map<String, dynamic> toJson() => {
        "rate": rate,
        "currencyCode": currencyCode,
      };
}

class BillMonth {
  final int id;
  final String fieldName;
  final String value;

  BillMonth({
    required this.id,
    required this.fieldName,
    required this.value,
  });

  factory BillMonth.fromJson(Map<String, dynamic> json) => BillMonth(
        id: json["id"],
        fieldName: json["field_name"],
        value: json["value"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "field_name": fieldName,
        "value": value,
      };
}
