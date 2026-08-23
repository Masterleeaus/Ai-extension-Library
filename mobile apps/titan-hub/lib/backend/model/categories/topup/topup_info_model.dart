import 'dart:convert';

TopUpInfoModel topUpInfoModelFromJson(String str) =>
    TopUpInfoModel.fromJson(json.decode(str));

String topUpInfoModelToJson(TopUpInfoModel data) => json.encode(data.toJson());

class TopUpInfoModel {
  Message message;
  Data data;

  TopUpInfoModel({
    required this.message,
    required this.data,
  });

  factory TopUpInfoModel.fromJson(Map<String, dynamic> json) => TopUpInfoModel(
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

  TopupCharge topupCharge;
  UserWallet userWallet;
  List<TopupType> topupTypes;
  List<Transaction> transactions;

  Data({
    required this.baseCurr,
    required this.baseCurrRate,
    required this.getRemainingFields,
    required this.topupCharge,
    required this.userWallet,
    required this.topupTypes,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        baseCurrRate: json["base_curr_rate"]?.toDouble() ?? 0.0,
        getRemainingFields:
            GetRemainingFields.fromJson(json["get_remaining_fields"]),
        topupCharge: TopupCharge.fromJson(json["topupCharge"]),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        topupTypes: List<TopupType>.from(
            json["topupTypes"].map((x) => TopupType.fromJson(x))),
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "base_curr_rate": baseCurrRate,
        "get_remaining_fields": getRemainingFields.toJson(),
        "topupCharge": topupCharge.toJson(),
        "userWallet": userWallet.toJson(),
        "topupTypes": List<dynamic>.from(topupTypes.map((x) => x.toJson())),
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

class TopupCharge {
  int id;
  String slug;
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;
  dynamic monthlyLimit;
  dynamic dailyLimit;

  TopupCharge({
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

  factory TopupCharge.fromJson(Map<String, dynamic> json) => TopupCharge(
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

class TopupType {
  int id;
  int adminId;
  String name;
  String slug;
  int status;
  DateTime createdAt;
  dynamic updatedAt;
  String editData;

  TopupType({
    required this.id,
    required this.adminId,
    required this.name,
    required this.slug,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    required this.editData,
  });

  factory TopupType.fromJson(Map<String, dynamic> json) => TopupType(
        id: json["id"],
        adminId: json["admin_id"],
        name: json["name"],
        slug: json["slug"],
        status: json["status"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: json["updated_at"] ?? '',
        editData: json["editData"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "admin_id": adminId,
        "name": name,
        "slug": slug,
        "status": status,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt,
        "editData": editData,
      };
}

class Transaction {
  int id;
  String trx;
  String transactionType;
  String requestAmount;
  String payable;
  String topupType;
  String mobileNumber;
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
    required this.topupType,
    required this.mobileNumber,
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
        topupType: json["topup_type"],
        mobileNumber: json["mobile_number"],
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
        "topup_type": topupType,
        "mobile_number": mobileNumber,
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

  UserWallet({
    required this.balance,
    required this.currency,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"]?.toDouble(),
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
