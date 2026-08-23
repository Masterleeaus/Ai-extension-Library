class PaymentLinkStoreModel {
  final Message message;
  final Data data;

  PaymentLinkStoreModel({
    required this.message,
    required this.data,
  });

  factory PaymentLinkStoreModel.fromJson(Map<String, dynamic> json) => PaymentLinkStoreModel(
    message: Message.fromJson(json["message"]),
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message.toJson(),
    "data": data.toJson(),
  };
}

class Data {
  final PaymentLink paymentLink;

  Data({
    required this.paymentLink,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    paymentLink: PaymentLink.fromJson(json["payment_link"]),
  );

  Map<String, dynamic> toJson() => {
    "payment_link": paymentLink.toJson(),
  };
}

class PaymentLink {
  final String currency;
  final String currencySymbol;
  final String country;
  final String currencyName;
  final String title;
  final String type;
  final dynamic details;
  final dynamic limit;
  final dynamic minAmount;
  final dynamic maxAmount;
  final String token;
  final int status;
  final int userId;
  final DateTime updatedAt;
  final DateTime createdAt;
  final int id;
  final String amountCalculation;
  final StringStatus stringStatus;
  final String linkType;
  final String shareLink;

  PaymentLink({
    required this.currency,
    required this.currencySymbol,
    required this.country,
    required this.currencyName,
    required this.title,
    required this.type,
    this.details,
     this.limit,
     this.minAmount,
    this.maxAmount,
    required this.token,
    required this.status,
    required this.userId,
    required this.updatedAt,
    required this.createdAt,
    required this.id,
    required this.amountCalculation,
    required this.stringStatus,
    required this.linkType,
    required this.shareLink,
  });

  factory PaymentLink.fromJson(Map<String, dynamic> json) => PaymentLink(
    currency: json["currency"],
    currencySymbol: json["currency_symbol"],
    country: json["country"],
    currencyName: json["currency_name"],
    title: json["title"],
    type: json["type"],
    details: json["details"]??'',
    limit: json["limit"]??'',
    minAmount: json["min_amount"]??'',
    maxAmount: json["max_amount"]??'',
    token: json["token"],
    status: json["status"],
    userId: json["user_id"],
    updatedAt: DateTime.parse(json["updated_at"]),
    createdAt: DateTime.parse(json["created_at"]),
    id: json["id"],
    amountCalculation: json["amountCalculation"],
    stringStatus: StringStatus.fromJson(json["stringStatus"]),
    linkType: json["linkType"],
    shareLink: json["shareLink"],
  );

  Map<String, dynamic> toJson() => {
    "currency": currency,
    "currency_symbol": currencySymbol,
    "country": country,
    "currency_name": currencyName,
    "title": title,
    "type": type,
    "details": details,
    "limit": limit,
    "min_amount": minAmount,
    "max_amount": maxAmount,
    "token": token,
    "status": status,
    "user_id": userId,
    "updated_at": updatedAt.toIso8601String(),
    "created_at": createdAt.toIso8601String(),
    "id": id,
    "amountCalculation": amountCalculation,
    "stringStatus": stringStatus.toJson(),
    "linkType": linkType,
    "shareLink": shareLink,
  };
}

class StringStatus {
  final String stringStatusClass;
  final String value;

  StringStatus({
    required this.stringStatusClass,
    required this.value,
  });

  factory StringStatus.fromJson(Map<String, dynamic> json) => StringStatus(
    stringStatusClass: json["class"],
    value: json["value"],
  );

  Map<String, dynamic> toJson() => {
    "class": stringStatusClass,
    "value": value,
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