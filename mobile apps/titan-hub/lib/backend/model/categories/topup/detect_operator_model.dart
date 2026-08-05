import 'dart:convert';

DetectOperatorModel detectOperatorModelFromJson(String str) =>
    DetectOperatorModel.fromJson(json.decode(str));

String detectOperatorModelToJson(DetectOperatorModel data) =>
    json.encode(data.toJson());

class DetectOperatorModel {
  final Message message;
  final DetectOperatorModelData data;

  DetectOperatorModel({
    required this.message,
    required this.data,
  });

  factory DetectOperatorModel.fromJson(Map<String, dynamic> json) =>
      DetectOperatorModel(
        message: Message.fromJson(json["message"]),
        data: DetectOperatorModelData.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class DetectOperatorModelData {
  final bool status;
  final String message;
  final DataData? data;

  DetectOperatorModelData({
    required this.status,
    required this.message,
    this.data,
  });

  factory DetectOperatorModelData.fromJson(Map<String, dynamic> json) =>
      DetectOperatorModelData(
        status: json["status"],
        message: json["message"],
        data: json["data"] != null ? DataData.fromJson(json["data"]) : null,
      );

  Map<String, dynamic> toJson() => {
        "status": status,
        "message": message,
        "data": data?.toJson(),
      };
}

class DataData {
  final int? id;
  final int? operatorId;
  final String? name;
  final bool? bundle;
  final bool? data;
  final bool? pin;
  final bool? supportsLocalAmounts;
  final bool? supportsGeographicalRechargePlans;
  final String? denominationType;
  final String? senderCurrencyCode;
  final String? senderCurrencySymbol;
  final String? destinationCurrencyCode;
  final String? destinationCurrencySymbol;
  final dynamic commission;
  final dynamic internationalDiscount;
  final dynamic localDiscount;
  final dynamic minAmount;
  final dynamic maxAmount;
  final dynamic localMinAmount;
  final dynamic localMaxAmount;
  final Country? country;
  final Fx? fx;
  final List<String>? logoUrls;
  final List<dynamic>? fixedAmounts;
  final List<dynamic>? fixedAmountsDescriptions;
  final List<dynamic>? localFixedAmounts;
  final Fees? fees;
  final String? status;
  final double? receiverCurrencyRate;
  final String? receiverCurrencyCode;

  DataData({
    this.id,
    this.operatorId,
    this.name,
    this.bundle,
    this.data,
    this.pin,
    this.supportsLocalAmounts,
    this.supportsGeographicalRechargePlans,
    this.denominationType,
    this.senderCurrencyCode,
    this.senderCurrencySymbol,
    this.destinationCurrencyCode,
    this.destinationCurrencySymbol,
    this.commission,
    this.internationalDiscount,
    this.localDiscount,
    this.minAmount,
    this.maxAmount,
    this.localMinAmount,
    this.localMaxAmount,
    this.country,
    this.fx,
    this.logoUrls,
    this.fixedAmounts,
    this.fixedAmountsDescriptions,
    this.localFixedAmounts,
    this.fees,
    this.status,
    this.receiverCurrencyRate,
    this.receiverCurrencyCode,
  });

  factory DataData.fromJson(Map<String, dynamic> json) => DataData(
        id: json["id"],
        operatorId: json["operatorId"],
        name: json["name"],
        bundle: json["bundle"],
        data: json["data"],
        pin: json["pin"],
        supportsLocalAmounts: json["supportsLocalAmounts"],
        supportsGeographicalRechargePlans:
            json["supportsGeographicalRechargePlans"],
        denominationType: json["denominationType"],
        senderCurrencyCode: json["senderCurrencyCode"],
        senderCurrencySymbol: json["senderCurrencySymbol"],
        destinationCurrencyCode: json["destinationCurrencyCode"],
        destinationCurrencySymbol: json["destinationCurrencySymbol"],
        commission: json["commission"],
        internationalDiscount: json["internationalDiscount"],
        localDiscount: json["localDiscount"],
        minAmount: json["minAmount"]?.toDouble(),
        maxAmount: json["maxAmount"]?.toDouble(),
        localMinAmount: json["localMinAmount"]?.toDouble(),
        localMaxAmount: json["localMaxAmount"]?.toDouble(),
        country:
            json["country"] != null ? Country.fromJson(json["country"]) : null,
        fx: json["fx"] != null ? Fx.fromJson(json["fx"]) : null,
        logoUrls: json["logoUrls"] != null
            ? List<String>.from(json["logoUrls"].map((x) => x))
            : null,
        fixedAmounts: json["fixedAmounts"] != null
            ? List<dynamic>.from(json["fixedAmounts"].map((x) => x))
            : null,
        fixedAmountsDescriptions: json["fixedAmountsDescriptions"] != null
            ? List<dynamic>.from(json["fixedAmountsDescriptions"].map((x) => x))
            : null,
        localFixedAmounts: json["localFixedAmounts"] != null
            ? List<dynamic>.from(json["localFixedAmounts"].map((x) => x))
            : null,
        fees: json["fees"] != null ? Fees.fromJson(json["fees"]) : null,
        status: json["status"],
        receiverCurrencyRate: json["receiver_currency_rate"]?.toDouble(),
        receiverCurrencyCode: json["receiver_currency_code"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "operatorId": operatorId,
        "name": name,
        "bundle": bundle,
        "data": data,
        "pin": pin,
        "supportsLocalAmounts": supportsLocalAmounts,
        "supportsGeographicalRechargePlans": supportsGeographicalRechargePlans,
        "denominationType": denominationType,
        "senderCurrencyCode": senderCurrencyCode,
        "senderCurrencySymbol": senderCurrencySymbol,
        "destinationCurrencyCode": destinationCurrencyCode,
        "destinationCurrencySymbol": destinationCurrencySymbol,
        "commission": commission,
        "internationalDiscount": internationalDiscount,
        "localDiscount": localDiscount,
        "minAmount": minAmount,
        "maxAmount": maxAmount,
        "localMinAmount": localMinAmount,
        "localMaxAmount": localMaxAmount,
        "country": country?.toJson(),
        "fx": fx?.toJson(),
        "logoUrls": logoUrls != null
            ? List<dynamic>.from(logoUrls!.map((x) => x))
            : null,
        "fixedAmounts": fixedAmounts != null
            ? List<dynamic>.from(fixedAmounts!.map((x) => x))
            : null,
        "fixedAmountsDescriptions": fixedAmountsDescriptions != null
            ? List<dynamic>.from(fixedAmountsDescriptions!.map((x) => x))
            : null,
        "localFixedAmounts": localFixedAmounts != null
            ? List<dynamic>.from(localFixedAmounts!.map((x) => x))
            : null,
        "fees": fees?.toJson(),
        "status": status,
        "receiver_currency_rate": receiverCurrencyRate,
        "receiver_currency_code": receiverCurrencyCode,
      };
}

class Country {
  final String isoName;
  final String name;

  Country({
    required this.isoName,
    required this.name,
  });

  factory Country.fromJson(Map<String, dynamic> json) => Country(
        isoName: json["isoName"],
        name: json["name"],
      );

  Map<String, dynamic> toJson() => {
        "isoName": isoName,
        "name": name,
      };
}

class Fees {
  final double? international;
  final dynamic local;
  final dynamic localPercentage;
  final double? internationalPercentage;

  Fees({
    this.international,
    required this.local,
    required this.localPercentage,
    this.internationalPercentage,
  });

  factory Fees.fromJson(Map<String, dynamic> json) => Fees(
        international: json["international"]?.toDouble() ?? 0.0,
        local: json["local"],
        localPercentage: json["localPercentage"],
        internationalPercentage:
            json["internationalPercentage"]?.toDouble() ?? 0.0,
      );

  Map<String, dynamic> toJson() => {
        "international": international,
        "local": local,
        "localPercentage": localPercentage,
        "internationalPercentage": internationalPercentage,
      };
}

class Fx {
  final dynamic rate;
  final String currencyCode;

  Fx({
    required this.rate,
    required this.currencyCode,
  });

  factory Fx.fromJson(Map<String, dynamic> json) => Fx(
        rate: json["rate"]?.toDouble() ?? 0.0,
        currencyCode: json["currencyCode"],
      );

  Map<String, dynamic> toJson() => {
        "rate": rate,
        "currencyCode": currencyCode,
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
