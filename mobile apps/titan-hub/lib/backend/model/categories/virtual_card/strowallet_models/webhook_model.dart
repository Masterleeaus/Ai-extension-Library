
class WebhookLogModel {
    Message message;
    Data data;

    WebhookLogModel({
        required this.message,
        required this.data,
    });

    factory WebhookLogModel.fromJson(Map<String, dynamic> json) => WebhookLogModel(
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
        transactions: List<Transaction>.from(json["transactions"].map((x) => Transaction.fromJson(x))),
    );

    Map<String, dynamic> toJson() => {
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
    };
}

class Transaction {
    String eventType;
    String event;
    String amount;
    String transitionId;
    String cardId;
    String reference;
    String? narrative;
    String? reason;
    String status;
    String? createdAt;
    String? chargeAmount;
    String? balanceBeforeTermination;

    Transaction({
        required this.eventType,
        required this.event,
        required this.amount,
        required this.transitionId,
        required this.cardId,
        required this.reference,
        this.narrative,
        this.reason,
        required this.status,
        this.createdAt,
        this.chargeAmount,
        this.balanceBeforeTermination,
    });

    factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        eventType: json["event_type"],
        event: json["event"],
        amount: json["amount"],
        transitionId: json["transition_id"],
        cardId: json["card_id"],
        reference: json["reference"],
        narrative: json["narrative"],
        reason: json["reason"],
        status: json["status"],
        createdAt: json["created_at"]??"",
        chargeAmount: json["charge_amount"],
        balanceBeforeTermination: json["balance_before_termination"],
    );

    Map<String, dynamic> toJson() => {
        "event_type": eventType,
        "event": event,
        "amount": amount,
        "transition_id": transitionId,
        "card_id": cardId,
        "reference": reference,
        "narrative": narrative,
        "reason": reason,
        "status": status,
        "created_at": createdAt,
        "charge_amount": chargeAmount,
        "balance_before_termination": balanceBeforeTermination,
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
