class SudoCardDetailsModel {
  Message message;
  Data data;

  SudoCardDetailsModel({
    required this.message,
    required this.data,
  });

  factory SudoCardDetailsModel.fromJson(Map<String, dynamic> json) =>
      SudoCardDetailsModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String baseCurr;
  CardSecureDate cardSecureDate;
  CardDetails cardDetails;

  Data({
    required this.baseCurr,
    required this.cardSecureDate,
    required this.cardDetails,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        cardSecureDate: CardSecureDate.fromJson(json["card_secure_date"]),
        cardDetails: CardDetails.fromJson(json["card_details"]),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "card_secure_date": cardSecureDate.toJson(),
        "card_details": cardDetails.toJson(),
      };
}

class CardDetails {
  int id;
  String cardId;
  int amount;
  String currency;
  String cardHolder;
  String brand;
  String type;
  String cardPan;
  String expiryMonth;
  String expiryYear;
  String cvv;
  String cardBackDetails;
  String cardBg;
  String siteTitle;
  String siteLogo;
  String status;
  bool isDefault;
  StatusInfo statusInfo;

  CardDetails({
    required this.id,
    required this.cardId,
    required this.amount,
    required this.currency,
    required this.cardHolder,
    required this.brand,
    required this.type,
    required this.cardPan,
    required this.expiryMonth,
    required this.expiryYear,
    required this.cvv,
    required this.cardBackDetails,
    required this.cardBg,
    required this.siteTitle,
    required this.siteLogo,
    required this.status,
    required this.isDefault,
    required this.statusInfo,
  });

  factory CardDetails.fromJson(Map<String, dynamic> json) => CardDetails(
        id: json["id"],
        cardId: json["card_id"],
        amount: json["amount"],
        currency: json["currency"],
        cardHolder: json["card_holder"],
        brand: json["brand"],
        type: json["type"],
        cardPan: json["card_pan"],
        expiryMonth: json["expiry_month"],
        expiryYear: json["expiry_year"],
        cvv: json["cvv"],
        cardBackDetails: json["card_back_details"],
        cardBg: json["card_bg"],
        siteTitle: json["site_title"],
        siteLogo: json["site_logo"],
        status: json["status"],
        isDefault: json["is_default"],
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "card_id": cardId,
        "amount": amount,
        "currency": currency,
        "card_holder": cardHolder,
        "brand": brand,
        "type": type,
        "card_pan": cardPan,
        "expiry_month": expiryMonth,
        "expiry_year": expiryYear,
        "cvv": cvv,
        "card_back_details": cardBackDetails,
        "card_bg": cardBg,
        "site_title": siteTitle,
        "site_logo": siteLogo,
        "status": status,
        "is_default": isDefault,
        "status_info": statusInfo.toJson(),
      };
}

class StatusInfo {
  int block;
  int unblock;

  StatusInfo({
    required this.block,
    required this.unblock,
  });

  factory StatusInfo.fromJson(Map<String, dynamic> json) => StatusInfo(
        block: json["block"],
        unblock: json["unblock"],
      );

  Map<String, dynamic> toJson() => {
        "block": block,
        "unblock": unblock,
      };
}

class CardSecureDate {
  String apiMode;
  String apiVaultId;
  String cardToken;

  CardSecureDate({
    required this.apiMode,
    required this.apiVaultId,
    required this.cardToken,
  });

  factory CardSecureDate.fromJson(Map<String, dynamic> json) => CardSecureDate(
        apiMode: json["api_mode"],
        apiVaultId: json["api_vault_id"],
        cardToken: json["card_token"],
      );

  Map<String, dynamic> toJson() => {
        "api_mode": apiMode,
        "api_vault_id": apiVaultId,
        "card_token": cardToken,
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
