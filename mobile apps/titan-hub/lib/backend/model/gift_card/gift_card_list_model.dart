// ignore_for_file: override_on_non_overriding_member

import 'dart:convert';

import '../../../widgets/dropdown/custom_dropdown_menu.dart';

GiftCardListModel giftCardListModelFromJson(String str) =>
    GiftCardListModel.fromJson(json.decode(str));

String giftCardListModelToJson(GiftCardListModel data) =>
    json.encode(data.toJson());

class GiftCardListModel {
  final Message message;
  final Data data;

  GiftCardListModel({
    required this.message,
    required this.data,
  });

  factory GiftCardListModel.fromJson(Map<String, dynamic> json) =>
      GiftCardListModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  final Products products;
  final List<CountryElement> countries;

  Data({
    required this.products,
    required this.countries,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        products: Products.fromJson(json["products"]),
        countries: List<CountryElement>.from(
            json["countries"].map((x) => CountryElement.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "products": products.toJson(),
        "countries": List<dynamic>.from(countries.map((x) => x.toJson())),
      };
}

class CountryElement implements DropdownMenuModel {
  final String name;
  final String mobileCode;
  final String currencyName;
  @override
  final String currencyCode;
  @override
  final String currencySymbol;
  final String iso2;

  CountryElement({
    required this.name,
    required this.mobileCode,
    required this.currencyName,
    required this.currencyCode,
    required this.currencySymbol,
    required this.iso2,
  });

  factory CountryElement.fromJson(Map<String, dynamic> json) => CountryElement(
        name: json["name"],
        mobileCode: json["mobile_code"],
        currencyName: json["currency_name"],
        currencyCode: json["currency_code"],
        currencySymbol: json["currency_symbol"],
        iso2: json["iso2"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "mobile_code": mobileCode,
        "currency_name": currencyName,
        "currency_code": currencyCode,
        "currency_symbol": currencySymbol,
        "iso2": iso2,
      };

  @override
  double get fCharge => 0.0;

  @override
  String get id => '';

  @override
  String get img => '';

  @override
  double get max => 0.0;

  @override
  String get mcode => '';

  @override
  double get min => 0.0;

  @override
  double get pCharge => 0.0;

  @override
  double get rate => 0.0;

  @override
  String get title => name;

  @override
  String get type => '';
}

class Products {
  final List<Datum> data;
  final int lastPage;

  Products({
    required this.data,
    required this.lastPage,
  });

  factory Products.fromJson(Map<String, dynamic> json) => Products(
        data: List<Datum>.from(json["data"].map((x) => Datum.fromJson(x))),
        lastPage: json["last_page"],
      );

  Map<String, dynamic> toJson() => {
        "data": List<dynamic>.from(data.map((x) => x.toJson())),
        "last_page": lastPage,
      };
}

class Datum {
  final int productId;
  final String productName;
  final String denominationType;
  final List<dynamic> fixedRecipientDenominations;
  final List<String> logoUrls;
  final DatumCountry country;

  Datum({
    required this.productId,
    required this.productName,
    required this.denominationType,
    required this.fixedRecipientDenominations,
    required this.logoUrls,
    required this.country,
  });

  factory Datum.fromJson(Map<String, dynamic> json) => Datum(
        productId: json["productId"],
        productName: json["productName"],
        denominationType: json["denominationType"],
        fixedRecipientDenominations: List<dynamic>.from(
            json["fixedRecipientDenominations"].map((x) => x)),
        logoUrls: List<String>.from(json["logoUrls"].map((x) => x)),
        country: DatumCountry.fromJson(json["country"]),
      );

  Map<String, dynamic> toJson() => {
        "productId": productId,
        "productName": productName,
        "denominationType": denominationType,
        "fixedRecipientDenominations":
            List<dynamic>.from(fixedRecipientDenominations.map((x) => x)),
        "logoUrls": List<dynamic>.from(logoUrls.map((x) => x)),
        "country": country.toJson(),
      };
}

class Brand {
  final int brandId;
  final String brandName;

  Brand({
    required this.brandId,
    required this.brandName,
  });

  factory Brand.fromJson(Map<String, dynamic> json) => Brand(
        brandId: json["brandId"],
        brandName: json["brandName"],
      );

  Map<String, dynamic> toJson() => {
        "brandId": brandId,
        "brandName": brandName,
      };
}

class DatumCountry {
  final String isoName;
  final String name;
  final String flagUrl;

  DatumCountry({
    required this.isoName,
    required this.name,
    required this.flagUrl,
  });

  factory DatumCountry.fromJson(Map<String, dynamic> json) => DatumCountry(
        isoName: json["isoName"],
        name: json["name"],
        flagUrl: json["flagUrl"],
      );

  Map<String, dynamic> toJson() => {
        "isoName": isoName,
        "name": name,
        "flagUrl": flagUrl,
      };
}

class Link {
  final String url;
  final String label;
  final bool active;

  Link({
    required this.url,
    required this.label,
    required this.active,
  });

  factory Link.fromJson(Map<String, dynamic> json) => Link(
        url: json["url"],
        label: json["label"],
        active: json["active"],
      );

  Map<String, dynamic> toJson() => {
        "url": url,
        "label": label,
        "active": active,
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
