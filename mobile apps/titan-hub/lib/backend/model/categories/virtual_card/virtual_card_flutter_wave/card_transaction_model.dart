import 'dart:convert';

CardTransactionModel cardTransactionModelFromJson(String str) =>
    CardTransactionModel.fromJson(json.decode(str));

String cardTransactionModelToJson(CardTransactionModel data) =>
    json.encode(data.toJson());

class CardTransactionModel {
  CardTransactionModel({
    required this.message,
    required this.data,
  });

  final Message message;
  final Data data;

  factory CardTransactionModel.fromJson(Map<String, dynamic> json) =>
      CardTransactionModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  Data({
    required this.cardTransactions,
  });

  final List<CardTransaction> cardTransactions;

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        cardTransactions: List<CardTransaction>.from(
            json["cardTransactions"].map((x) => CardTransaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "cardTransactions":
            List<dynamic>.from(cardTransactions.map((x) => x.toJson())),
      };
}

class CardTransaction {
  CardTransaction({
    required this.trx,
    required this.amount,
    required this.paymentDetails,
    required this.reference,
    required this.gatewayReference,
    required this.responseMessage,
    required this.status,
    required this.date,
  });

  final int trx;
  final String amount;
  final String paymentDetails;
  final String reference;
  final String gatewayReference;
  final String responseMessage;
  final String status;
  final String date;

  factory CardTransaction.fromJson(Map<String, dynamic> json) =>
      CardTransaction(
        trx: json["trx"],
        amount: json["amount"],
        paymentDetails: json["payment_details"],
        reference: json["reference"],
        gatewayReference: json["gateway_reference"],
        responseMessage: json["response_message"],
        status: json["status"],
        date: json["date"],
      );

  Map<String, dynamic> toJson() => {
        "trx": trx,
        "amount": amount,
        "payment_details": paymentDetails,
        "reference": reference,
        "gateway_reference": gatewayReference,
        "response_message": responseMessage,
        "status": status,
        "date": date,
      };
}

class Message {
  Message({
    required this.success,
  });

  final List<String> success;

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
