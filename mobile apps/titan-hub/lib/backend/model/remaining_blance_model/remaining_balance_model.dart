class RemainingBalanceModel {
  Message message;
  Data data;

  RemainingBalanceModel({
    required this.message,
    required this.data,
  });

  factory RemainingBalanceModel.fromJson(Map<String, dynamic> json) =>
      RemainingBalanceModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  bool status;
  String transactionType;
  double remainingDaily;
  double remainingMonthly;
  String currency;

  Data({
    required this.status,
    required this.transactionType,
    required this.remainingDaily,
    required this.remainingMonthly,
    required this.currency,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        status: json["status"] ?? false,
        transactionType: json["transaction_type"] ?? "",
        remainingDaily: _parseDouble(json["remainingDaily"]),
        remainingMonthly: _parseDouble(json["remainingMonthly"]),
        currency: json["currency"] ?? "",
      );

  Map<String, dynamic> toJson() => {
        "status": status,
        "transaction_type": transactionType,
        "remainingDaily": remainingDaily,
        "remainingMonthly": remainingMonthly,
        "currency": currency,
      };

  // Helper function to safely parse numbers
  static double _parseDouble(dynamic value) {
    if (value == null) return 0.0;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value) ?? 0.0;
    return 0.0;
  }
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
