import 'dart:convert';

RequestMoneyLogModel requestMoneyLogModelFromJson(String str) =>
    RequestMoneyLogModel.fromJson(json.decode(str));

String requestMoneyLogModelToJson(RequestMoneyLogModel data) =>
    json.encode(data.toJson());

class RequestMoneyLogModel {
  Message message;
  Data data;

  RequestMoneyLogModel({
    required this.message,
    required this.data,
  });

  factory RequestMoneyLogModel.fromJson(Map<String, dynamic> json) =>
      RequestMoneyLogModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  List<Transaction> transactions;

  Data({
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
      };
}

class Transaction {
  int id;
  String trx;
  String requestType;
  String title;
  String requestAmount;
  String charge;
  String payable;
  int status;
  Map<String, String> statusInfo;
  bool action;
  DateTime createdAt;

  Transaction({
    required this.id,
    required this.trx,
    required this.requestType,
    required this.title,
    required this.requestAmount,
    required this.charge,
    required this.payable,
    required this.status,
    required this.statusInfo,
    required this.action,
    required this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        trx: json["trx"],
        requestType: json["request_type"],
        title: json["title"],
        requestAmount: json["request_amount"],
        charge: json["charge"],
        payable: json["payable"],
        status: json["status"],
        statusInfo: Map.from(json["status_info"])
            .map((k, v) => MapEntry<String, String>(k, v)),
        action: json["action"],
        createdAt: DateTime.parse(json["created_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "request_type": requestType,
        "title": title,
        "request_amount": requestAmount,
        "charge": charge,
        "payable": payable,
        "status": status,
        "status_info":
            Map.from(statusInfo).map((k, v) => MapEntry<String, dynamic>(k, v)),
        "action": action,
        "created_at": createdAt.toIso8601String(),
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
