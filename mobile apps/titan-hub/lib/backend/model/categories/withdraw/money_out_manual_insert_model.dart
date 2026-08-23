
class WithdrawManualInsertModel {
  WithdrawManualInsertModel({
    required this.message,
    required this.data,
  });

  Message message;
  Data data;

  factory WithdrawManualInsertModel.fromJson(Map<String, dynamic> json) =>
      WithdrawManualInsertModel(
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
    required this.paymentInformations,
    required this.gatewayType,
    required this.gatewayCurrencyName,
    required this.alias,
    required this.details,
    required this.inputFields,
    required this.url,
    required this.method,
  });

  PaymentInformations paymentInformations;
  dynamic gatewayType;
  dynamic gatewayCurrencyName;
  dynamic alias;
  dynamic details;
  List<InputField> inputFields;
  dynamic url;
  dynamic method;

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        paymentInformations:
            PaymentInformations.fromJson(json["payment_informations"]),
        gatewayType: json["gateway_type"] ?? "",
        gatewayCurrencyName: json["gateway_currency_name"] ?? "",
        alias: json["alias"] ?? "",
        details: json["details"] ?? "",
        inputFields: List<InputField>.from(
            json["input_fields"].map((x) => InputField.fromJson(x))),
        url: json["url"] ?? "",
        method: json["method"] ?? "",
      );

  Map<String, dynamic> toJson() => {
        "payment_informations": paymentInformations.toJson(),
        "gateway_type": gatewayType,
        "gateway_currency_name": gatewayCurrencyName,
        "alias": alias,
        "details": details,
        "input_fields": List<dynamic>.from(inputFields.map((x) => x.toJson())),
        "url": url,
        "method": method,
      };
}

class InputField {
  InputField({
    required this.type,
    required this.label,
    required this.name,
    required this.required,
    required this.validation,
  });

  dynamic type;
  dynamic label;
  dynamic name;
  bool required;
  Validation validation;

  factory InputField.fromJson(Map<String, dynamic> json) => InputField(
        type: json["type"] ?? "",
        label: json["label"] ?? "",
        name: json["name"] ?? "",
        required: json["required"],
        validation: Validation.fromJson(json["validation"]),
      );

  Map<String, dynamic> toJson() => {
        "type": type,
        "label": label,
        "name": name,
        "required": required,
        "validation": validation.toJson(),
      };
}

class Validation {
  Validation({
    required this.max,
    required this.mimes,
    required this.min,
    required this.options,
    required this.required,
  });

  dynamic max;
  List<String> mimes;
  dynamic min;
  List<dynamic> options;
  bool required;

  factory Validation.fromJson(Map<String, dynamic> json) => Validation(
        max: json["max"] ?? "",
        mimes: List<String>.from(json["mimes"].map((x) => x)),
        min: json["min"],
        options: List<dynamic>.from(json["options"].map((x) => x)),
        required: json["required"],
      );

  Map<String, dynamic> toJson() => {
        "max": max,
        "mimes": List<dynamic>.from(mimes.map((x) => x)),
        "min": min,
        "options": List<dynamic>.from(options.map((x) => x)),
        "required": required,
      };
}

class PaymentInformations {
  PaymentInformations({
    required this.trx,
    required this.gatewayCurrencyName,
    required this.requestAmount,
    required this.exchangeRate,
    required this.conversionAmount,
    required this.totalCharge,
    required this.willGet,
  });

  dynamic trx;
  dynamic gatewayCurrencyName;
  dynamic requestAmount;
  dynamic exchangeRate;
  dynamic conversionAmount;
  dynamic totalCharge;
  dynamic willGet;

  factory PaymentInformations.fromJson(Map<String, dynamic> json) =>
      PaymentInformations(
        trx: json["trx"] ?? "",
        gatewayCurrencyName: json["gateway_currency_name"] ?? "",
        requestAmount: json["request_amount"] ?? "",
        exchangeRate: json["exchange_rate"] ?? "",
        conversionAmount: json["conversion_amount"] ?? "",
        totalCharge: json["total_charge"] ?? "",
        willGet: json["will_get"] ?? "",
      );

  Map<String, dynamic> toJson() => {
        "trx": trx,
        "gateway_currency_name": gatewayCurrencyName,
        "request_amount": requestAmount,
        "exchange_rate": exchangeRate,
        "conversion_amount": conversionAmount,
        "total_charge": totalCharge,
        "will_get": willGet,
      };
}

class Message {
  Message({
    required this.success,
  });

  List<String> success;

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}
