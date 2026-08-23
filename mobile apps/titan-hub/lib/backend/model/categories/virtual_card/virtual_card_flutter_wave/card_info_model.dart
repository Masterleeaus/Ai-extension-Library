import 'dart:convert';

CardInfoModel cardInfoModelFromJson(String str) =>
    CardInfoModel.fromJson(json.decode(str));

String cardInfoModelToJson(CardInfoModel data) => json.encode(data.toJson());

class CardInfoModel {
  final Message message;
  final Data data;

  CardInfoModel({
    required this.message,
    required this.data,
  });

  factory CardInfoModel.fromJson(Map<String, dynamic> json) => CardInfoModel(
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
  final bool cardCreateAction;
  final CardBasicInfo cardBasicInfo;
  final List<MyCard> myCard;
  final UserWallet userWallet;
  final CardCharge cardCharge;
  final List<Transaction> transactions;

  Data({
    required this.baseCurr,
    required this.cardCreateAction,
    required this.cardBasicInfo,
    required this.myCard,
    required this.userWallet,
    required this.cardCharge,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        cardCreateAction: json["card_create_action"],
        cardBasicInfo: CardBasicInfo.fromJson(json["card_basic_info"]),
        myCard:
            List<MyCard>.from(json["myCard"].map((x) => MyCard.fromJson(x))),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        cardCharge: CardCharge.fromJson(json["cardCharge"]),
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "card_create_action": cardCreateAction,
        "card_basic_info": cardBasicInfo.toJson(),
        "myCard": List<dynamic>.from(myCard.map((x) => x.toJson())),
        "userWallet": userWallet.toJson(),
        "cardCharge": cardCharge.toJson(),
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
      };
}

class CardBasicInfo {
  final int cardCreateLimit;
  final String cardBackDetails;
  final String cardBg;
  final String siteTitle;
  final String siteLogo;

  CardBasicInfo({
    required this.cardCreateLimit,
    required this.cardBackDetails,
    required this.cardBg,
    required this.siteTitle,
    required this.siteLogo,
  });

  factory CardBasicInfo.fromJson(Map<String, dynamic> json) => CardBasicInfo(
        cardCreateLimit: json["card_create_limit"],
        cardBackDetails: json["card_back_details"],
        cardBg: json["card_bg"],
        siteTitle: json["site_title"],
        siteLogo: json["site_logo"],
      );

  Map<String, dynamic> toJson() => {
        "card_create_limit": cardCreateLimit,
        "card_back_details": cardBackDetails,
        "card_bg": cardBg,
        "site_title": siteTitle,
        "site_logo": siteLogo,
      };
}

class CardCharge {
  final int id;
  final String slug;
  final String title;
  final double fixedCharge;
  final double percentCharge;
  final double minLimit;
  final double maxLimit;

  CardCharge({
    required this.id,
    required this.slug,
    required this.title,
    required this.fixedCharge,
    required this.percentCharge,
    required this.minLimit,
    required this.maxLimit,
  });

  factory CardCharge.fromJson(Map<String, dynamic> json) => CardCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"]?.toDouble() ?? 0.0,
        percentCharge: json["percent_charge"]?.toDouble() ?? 0.0,
        minLimit: json["min_limit"]?.toDouble() ?? 0.0,
        maxLimit: json["max_limit"]?.toDouble() ?? 0.0,
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "slug": slug,
        "title": title,
        "fixed_charge": fixedCharge,
        "percent_charge": percentCharge,
        "min_limit": minLimit,
        "max_limit": maxLimit,
      };
}

class MyCard {
  final int id;
  final String name;
  final String cardPan;
  final String cardId;
  final String expiration;
  final String cvv;
  final int amount;
  final int status;
  final dynamic isDefault;
  final MyCardStatusInfo statusInfo;

  MyCard({
    required this.id,
    required this.name,
    required this.cardPan,
    required this.cardId,
    required this.expiration,
    required this.cvv,
    required this.amount,
    required this.status,
    this.isDefault,
    required this.statusInfo,
  });

  factory MyCard.fromJson(Map<String, dynamic> json) => MyCard(
        id: json["id"],
        name: json["name"],
        cardPan: json["card_pan"],
        cardId: json["card_id"],
        expiration: json["expiration"],
        cvv: json["cvv"],
        amount: json["amount"],
        status: json["status"],
        isDefault: json["is_default"] ?? false,
        statusInfo: MyCardStatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "card_pan": cardPan,
        "card_id": cardId,
        "expiration": expiration,
        "cvv": cvv,
        "amount": amount,
        "status": status,
        "is_default": isDefault,
        "status_info": statusInfo.toJson(),
      };
}

class MyCardStatusInfo {
  final int block;
  final int unblock;

  MyCardStatusInfo({
    required this.block,
    required this.unblock,
  });

  factory MyCardStatusInfo.fromJson(Map<String, dynamic> json) =>
      MyCardStatusInfo(
        block: json["block"],
        unblock: json["unblock"],
      );

  Map<String, dynamic> toJson() => {
        "block": block,
        "unblock": unblock,
      };
}

class Transaction {
  final int id;
  final String trx;
  final String transactionType;
  final String requestAmount;
  final String payable;
  final String totalCharge;
  final String cardAmount;
  final String cardNumber;
  final String currentBalance;
  final String status;
  final DateTime dateTime;
  final TransactionStatusInfo statusInfo;

  Transaction({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.totalCharge,
    required this.cardAmount,
    required this.cardNumber,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        trx: json["trx"],
        transactionType: json["transaction_type"],
        requestAmount: json["request_amount"],
        payable: json["payable"],
        totalCharge: json["total_charge"],
        cardAmount: json["card_amount"],
        cardNumber: json["card_number"],
        currentBalance: json["current_balance"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: TransactionStatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "total_charge": totalCharge,
        "card_amount": cardAmount,
        "card_number": cardNumber,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class TransactionStatusInfo {
  final int success;
  final int pending;
  final int rejected;

  TransactionStatusInfo({
    required this.success,
    required this.pending,
    required this.rejected,
  });

  factory TransactionStatusInfo.fromJson(Map<String, dynamic> json) =>
      TransactionStatusInfo(
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
  final double balance;
  final String currency;

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
