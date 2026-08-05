import 'dart:convert';

import '../../../widgets/payment_link/custom_drop_down.dart';

PaymentLinkEditModel paymentLinkEditModelFromJson(String str) =>
    PaymentLinkEditModel.fromJson(json.decode(str));

String paymentLinkEditModelToJson(PaymentLinkEditModel data) =>
    json.encode(data.toJson());

class PaymentLinkEditModel {
  final Message message;
  final Data data;

  PaymentLinkEditModel({
    required this.message,
    required this.data,
  });

  factory PaymentLinkEditModel.fromJson(Map<String, dynamic> json) =>
      PaymentLinkEditModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  final String baseUrl;
  final String defaultImage;
  final String imagePath;
  final List<CurrencyDatum> currencyData;
  final PaymentLink paymentLink;

  Data({
    required this.baseUrl,
    required this.defaultImage,
    required this.imagePath,
    required this.currencyData,
    required this.paymentLink,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseUrl: json["base_url"],
        defaultImage: json["default_image"],
        imagePath: json["image_path"],
        currencyData: List<CurrencyDatum>.from(
            json["currency_data"].map((x) => CurrencyDatum.fromJson(x))),
        paymentLink: PaymentLink.fromJson(json["payment_link"]),
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "default_image": defaultImage,
        "image_path": imagePath,
        "currency_data":
            List<dynamic>.from(currencyData.map((x) => x.toJson())),
        "payment_link": paymentLink.toJson(),
      };
}

class CurrencyDatum implements DropdownModel {
  final dynamic currencyName;
  final dynamic currencyCode;
  final dynamic country;
  final dynamic currencySymbol;

  CurrencyDatum({
    this.currencyName,
    this.currencyCode,
    this.country,
    this.currencySymbol,
  });

  factory CurrencyDatum.fromJson(Map<String, dynamic> json) => CurrencyDatum(
        currencyName: json["currency_name"] ?? '',
        currencyCode: json["currency_code"] ?? "",
        country: json["country"] ?? '',
        currencySymbol: json["currency_symbol"] ?? "",
      );

  Map<String, dynamic> toJson() => {
        "currency_name": currencyName,
        "currency_code": currencyCode,
        "country": country,
        "currency_symbol": currencySymbol,
      };

  @override
  // ignore: prefer_interpolation_to_compose_strings
  String get title => currencyCode + '-' + currencyName!;
}

class PaymentLink {
  final int id;
  final String currency;
  final String currencyName;
  final String currencySymbol;
  final String country;
  final String type;
  final String token;
  final String title;
  final dynamic image;
  final dynamic details;
  final dynamic limit;
  final dynamic minAmount;
  final dynamic maxAmount;
  final dynamic price;
  final dynamic qty;
  final dynamic status;
  final String stringStatus;
  final DateTime createdAt;

  PaymentLink({
    required this.id,
    required this.currency,
    required this.currencyName,
    required this.currencySymbol,
    required this.country,
    required this.type,
    required this.token,
    required this.title,
    this.image,
    this.details,
    this.limit,
    this.minAmount,
    this.maxAmount,
    this.price,
    this.qty,
    required this.status,
    required this.stringStatus,
    required this.createdAt,
  });

  factory PaymentLink.fromJson(Map<String, dynamic> json) => PaymentLink(
        id: json["id"],
        currency: json["currency"],
        currencyName: json["currency_name"],
        currencySymbol: json["currency_symbol"],
        country: json["country"],
        type: json["type"],
        token: json["token"],
        title: json["title"],
        image: json["image"] ?? '',
        details: json["details"] ?? '',
        limit: json["limit"] ?? '',
        minAmount: (json["min_amount"] != null)
            ? double.parse(json["min_amount"].toString()).toStringAsFixed(2)
            : '',
        maxAmount: (json["max_amount"] != null)
            ? double.parse(json["max_amount"].toString()).toStringAsFixed(2)
            : '',
        price: (json["price"] != null)
            ? double.parse(json["price"].toString()).toStringAsFixed(2)
            : '',
        qty: json["qty"] ?? '',
        status: json["status"],
        stringStatus: json["string_status"],
        createdAt: DateTime.parse(json["created_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "currency": currency,
        "currency_name": currencyName,
        "currency_symbol": currencySymbol,
        "country": country,
        "type": type,
        "token": token,
        "title": title,
        "image": image,
        "details": details,
        "limit": limit,
        "min_amount": minAmount,
        "max_amount": maxAmount,
        "price": price,
        "qty": qty,
        "status": status,
        "string_status": stringStatus,
        "created_at": createdAt.toIso8601String(),
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
