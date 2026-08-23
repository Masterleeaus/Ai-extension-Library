// ignore_for_file: override_on_non_overriding_member

import 'dart:convert';

import '../../../widgets/dropdown/custom_dropdown_menu.dart';

GiftCardDetailsModel giftCardDetailsModelFromJson(String str) =>
    GiftCardDetailsModel.fromJson(json.decode(str));

String giftCardDetailsModelToJson(GiftCardDetailsModel data) =>
    json.encode(data.toJson());

class GiftCardDetailsModel {
  final Message message;
  final Data data;

  GiftCardDetailsModel({
    required this.message,
    required this.data,
  });

  factory GiftCardDetailsModel.fromJson(Map<String, dynamic> json) =>
      GiftCardDetailsModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  final Product product;
  final List<ProductCurrency> productCurrency;
  final List<UserWallet> userWallet;
  final CardCharge cardCharge;
  final List<CountryElement> countries;

  Data({
    required this.product,
    required this.productCurrency,
    required this.userWallet,
    required this.cardCharge,
    required this.countries,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        product: Product.fromJson(json["product"]),
        productCurrency: List<ProductCurrency>.from(
            json["productCurrency"].map((x) => ProductCurrency.fromJson(x))),
        userWallet: List<UserWallet>.from(
            json["userWallet"].map((x) => UserWallet.fromJson(x))),
        cardCharge: CardCharge.fromJson(json["cardCharge"]),
        countries: List<CountryElement>.from(
            json["countries"].map((x) => CountryElement.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "product": product.toJson(),
        "productCurrency":
            List<dynamic>.from(productCurrency.map((x) => x.toJson())),
        "userWallet": List<dynamic>.from(userWallet.map((x) => x.toJson())),
        "cardCharge": cardCharge.toJson(),
        "countries": List<dynamic>.from(countries.map((x) => x.toJson())),
      };
}

class CardCharge {
  final int id;
  final String slug;
  final String title;
  final String fixedCharge;
  final String percentCharge;
  final String minLimit;
  final String maxLimit;

  CardCharge({
    required this.id,
    required this.slug,
    required this.title,
    required this.fixedCharge,
    required this.percentCharge,
    required this.minLimit,
    required this.maxLimit,
  });

  factory CardCharge.fromJson(Map<String, dynamic> json) => CardCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"],
        percentCharge: json["percent_charge"],
        minLimit: json["min_limit"],
        maxLimit: json["max_limit"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "slug": slug,
        "title": title,
        "fixed_charge": fixedCharge,
        "percent_charge": percentCharge,
        "min_limit": minLimit,
        "max_limit": maxLimit,
      };
}

class CountryElement implements DropdownMenuModel {
  final String name;
  final String mobileCode;
  final String currencyName;

  final String currencyCode;

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

class Product {
  final int productId;
  final String productName;
  final String denominationType;
  final List<dynamic> fixedRecipientDenominations;
  final List<dynamic> fixedSenderDenominations;
  final List<String> logoUrls;
  final ProductCountry country;

  Product({
    required this.productId,
    required this.productName,
    required this.denominationType,
    required this.fixedRecipientDenominations,
    required this.fixedSenderDenominations,
    required this.logoUrls,
    required this.country,
  });

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        productId: json["productId"],
        productName: json["productName"],
        denominationType: json["denominationType"],
        fixedRecipientDenominations: List<dynamic>.from(
            (json["fixedRecipientDenominations"] ?? []).map((x) => x)),
        fixedSenderDenominations: List<dynamic>.from(
            (json["fixedSenderDenominations"] ?? []).map((x) => x?.toDouble())),
        logoUrls: List<String>.from(json["logoUrls"].map((x) => x)),
        country: ProductCountry.fromJson(json["country"]),
      );

  Map<String, dynamic> toJson() => {
        "productId": productId,
        "productName": productName,
        "denominationType": denominationType,
        "fixedRecipientDenominations":
            List<dynamic>.from(fixedRecipientDenominations.map((x) => x)),
        "fixedSenderDenominations":
            List<dynamic>.from(fixedSenderDenominations.map((x) => x)),
        "logoUrls": List<dynamic>.from(logoUrls.map((x) => x)),
        "country": country.toJson(),
      };
}

class ProductCountry {
  final String isoName;
  final String name;
  final String flagUrl;

  ProductCountry({
    required this.isoName,
    required this.name,
    required this.flagUrl,
  });

  factory ProductCountry.fromJson(Map<String, dynamic> json) => ProductCountry(
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

class ProductCurrency {
  final String name;
  final String currencyCode;
  final int rate;

  ProductCurrency({
    required this.name,
    required this.currencyCode,
    required this.rate,
  });

  factory ProductCurrency.fromJson(Map<String, dynamic> json) =>
      ProductCurrency(
        name: json["name"],
        currencyCode: json["currency_code"],
        rate: json["rate"],
      );

  Map<String, dynamic> toJson() => {
        "name": name,
        "currency_code": currencyCode,
        "rate": rate,
      };
}

class UserWallet implements DropdownMenuModel {
  final String name;
  final double balance;
  @override
  final String currencyCode;
  @override
  final String currencySymbol;
  final String currencyType;
  @override
  final double rate;
  final String flag;
  final String imagePath;

  UserWallet({
    required this.name,
    required this.balance,
    required this.currencyCode,
    required this.currencySymbol,
    required this.currencyType,
    required this.rate,
    required this.flag,
    required this.imagePath,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        name: json["name"],
        balance: json["balance"]?.toDouble(),
        currencyCode: json["currency_code"],
        currencySymbol: json["currency_symbol"],
        currencyType: json["currency_type"],
        rate: json["rate"]?.toDouble() ?? 0.0,
        flag: json["flag"],
        imagePath: json["image_path"],
      );

  Map<String, dynamic> toJson() => {
        "name": name,
        "balance": balance,
        "currency_code": currencyCode,
        "currency_symbol": currencySymbol,
        "currency_type": currencyType,
        "rate": rate,
        "flag": flag,
        "image_path": imagePath,
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
  String get title => name;

  @override
  String get type => '';
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
