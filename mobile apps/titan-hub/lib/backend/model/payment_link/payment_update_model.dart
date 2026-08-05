
class PaymentLinkUpdateModel {
  final Message message;
  final Data data;

  PaymentLinkUpdateModel({
    required this.message,
    required this.data,
  });

  factory PaymentLinkUpdateModel.fromJson(Map<String, dynamic> json) => PaymentLinkUpdateModel(
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
  final String linkType;
  final String shareLink;

  PaymentLink({
    required this.linkType,
    required this.shareLink,
  });

  factory PaymentLink.fromJson(Map<String, dynamic> json) => PaymentLink(
    linkType: json["linkType"],
    shareLink: json["shareLink"],
  );

  Map<String, dynamic> toJson() => {
    "linkType": linkType,
    "shareLink": shareLink,
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