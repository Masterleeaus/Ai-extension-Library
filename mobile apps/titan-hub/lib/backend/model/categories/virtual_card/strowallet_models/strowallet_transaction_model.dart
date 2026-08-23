
class StrowalletCardTransactionModel {
  Message message;
  Data data;

  StrowalletCardTransactionModel({
    required this.message,
    required this.data,
  });

  factory StrowalletCardTransactionModel.fromJson(Map<String, dynamic> json) =>
      StrowalletCardTransactionModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  List<CardTransaction> cardTransactions;

  Data({
    required this.cardTransactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        cardTransactions: List<CardTransaction>.from(
            json["card_transactions"].map((x) => CardTransaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "card_transactions":
            List<dynamic>.from(cardTransactions.map((x) => x.toJson())),
      };
}

class CardTransaction {
  String id;
  DateTime createdAt;
  DateTime updatedAt;
  String amount;
  String centAmount;
  String type;
  String method;
  String narrative;
  String status;
  String currency;
  String reference;
  String cardId;

  CardTransaction({
    required this.id,
    required this.createdAt,
    required this.updatedAt,
    required this.amount,
    required this.centAmount,
    required this.type,
    required this.method,
    required this.narrative,
    required this.status,
    required this.currency,
    required this.reference,
    required this.cardId,
  });

  factory CardTransaction.fromJson(Map<String, dynamic> json) =>
      CardTransaction(
        id: json["id"],
        createdAt: DateTime.parse(json["createdAt"]),
        updatedAt: DateTime.parse(json["updatedAt"]),
        amount: json["amount"],
        centAmount: json["centAmount"],
        type: json["type"],
        method: json["method"],
        narrative: json["narrative"],
        status: json["status"],
        currency: json["currency"],
        reference: json["reference"],
        cardId: json["cardId"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "createdAt": createdAt.toIso8601String(),
        "updatedAt": updatedAt.toIso8601String(),
        "amount": amount,
        "centAmount": centAmount,
        "type": type,
        "method": method,
        "narrative": narrative,
        "status": status,
        "currency": currency,
        "reference": reference,
        "cardId": cardId,
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
