
class AgentMoneyOutInfoModel {
  Message message;
  Data data;

  AgentMoneyOutInfoModel({
    required this.message,
    required this.data,
  });

  factory AgentMoneyOutInfoModel.fromJson(Map<String, dynamic> json) =>
      AgentMoneyOutInfoModel(
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
  MoneyOutCharge moneyOutCharge;
  UserWallet userWallet;
  List<Transaction> transactions;

  Data({
    required this.baseCurr,
    required this.baseCurrRate,
    required this.getRemainingFields,
    required this.moneyOutCharge,
    required this.userWallet,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        baseCurrRate: json["base_curr_rate"]?.toDouble()??0.0,
          getRemainingFields: GetRemainingFields.fromJson(json["get_remaining_fields"]),

        moneyOutCharge: MoneyOutCharge.fromJson(json["moneyOutCharge"]),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "base_curr_rate": baseCurrRate,
          "get_remaining_fields": getRemainingFields.toJson(),
                "moneyOutCharge": moneyOutCharge.toJson(),
        "userWallet": userWallet.toJson(),
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

    factory GetRemainingFields.fromJson(Map<String, dynamic> json) => GetRemainingFields(
        transactionType: json["transaction_type"],
        attribute: json["attribute"],
    );

    Map<String, dynamic> toJson() => {
        "transaction_type": transactionType,
        "attribute": attribute,
    };
}

class MoneyOutCharge {
  int id;
  String slug;
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;
  dynamic monthlyLimit;
  dynamic dailyLimit;
  dynamic agentFixedCommissions;
  dynamic agentPercentCommissions;
  bool agentProfit;

  MoneyOutCharge({
    required this.id,
    required this.slug,
    required this.title,
    required this.fixedCharge,
    required this.percentCharge,
    required this.minLimit,
    required this.maxLimit,
    required this.monthlyLimit,
    required this.dailyLimit,
    required this.agentFixedCommissions,
    required this.agentPercentCommissions,
    required this.agentProfit,
  });

  factory MoneyOutCharge.fromJson(Map<String, dynamic> json) => MoneyOutCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"]?.toDouble()??0.0,
        percentCharge: json["percent_charge"]?.toDouble()??0.0,
        minLimit: json["min_limit"]?.toDouble()??0.0,
        maxLimit: json["max_limit"]?.toDouble()??0.0,
        monthlyLimit: json["monthly_limit"]?.toDouble()??0.0,
        dailyLimit: json["daily_limit"]?.toDouble()??0.0,
        agentFixedCommissions: json["agent_fixed_commissions"]?.toDouble()??0.0,
        agentPercentCommissions: json["agent_percent_commissions"]?.toDouble()??0.0,
        agentProfit: json["agent_profit"],
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
        "agent_fixed_commissions": agentFixedCommissions,
        "agent_percent_commissions": agentPercentCommissions,
        "agent_profit": agentProfit,
      };
}

class Transaction {
  int id;
  String type;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String totalCharge;
  String payable;
  String recipientReceived;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;

  Transaction({
    required this.id,
    required this.type,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.totalCharge,
    required this.payable,
    required this.recipientReceived,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        type: json["type"],
        trx: json["trx"],
        transactionType: json["transaction_type"],
        transactionHeading: json["transaction_heading"],
        requestAmount: json["request_amount"],
        totalCharge: json["total_charge"],
        payable: json["payable"],
        recipientReceived: json["recipient_received"],
        currentBalance: json["current_balance"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "total_charge": totalCharge,
        "payable": payable,
        "recipient_received": recipientReceived,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
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
  int rate;

  UserWallet({
    required this.balance,
    required this.currency,
    required this.rate,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"]?.toDouble(),
        currency: json["currency"],
        rate: json["rate"],
      );

  Map<String, dynamic> toJson() => {
        "balance": balance,
        "currency": currency,
        "rate": rate,
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
