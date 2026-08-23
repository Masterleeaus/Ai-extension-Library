
import 'package:qrpay/widgets/payment_link/custom_drop_down.dart';


class WithdrawFlutterWaveInsertModel {
  Message message;
  Data data;

  WithdrawFlutterWaveInsertModel({
    required this.message,
    required this.data,
  });

  factory WithdrawFlutterWaveInsertModel.fromJson(Map<String, dynamic> json) =>
      WithdrawFlutterWaveInsertModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  PaymentInformations paymentInformations;
  String gatewayType;
  String gatewayCurrencyName;
  String gatewayCurrencyCode;
  bool branchAvailable;
  String alias;
  List<InputField> inputFields;
  String url;
  String method;

  Data({
    required this.paymentInformations,
    required this.gatewayType,
    required this.gatewayCurrencyName,
    required this.gatewayCurrencyCode,
    required this.branchAvailable,
    required this.alias,
    required this.inputFields,
    required this.url,
    required this.method,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        paymentInformations:
            PaymentInformations.fromJson(json["payment_informations"]),
        gatewayType: json["gateway_type"],
        gatewayCurrencyName: json["gateway_currency_name"],
        gatewayCurrencyCode: json["gateway_currency_code"],
        branchAvailable: json["branch_available"],
        alias: json["alias"],
        inputFields: List<InputField>.from(
            json["input_fields"].map((x) => InputField.fromJson(x))),
        url: json["url"],
        method: json["method"],
      );

  Map<String, dynamic> toJson() => {
        "payment_informations": paymentInformations.toJson(),
        "gateway_type": gatewayType,
        "gateway_currency_name": gatewayCurrencyName,
        "gateway_currency_code": gatewayCurrencyCode,
        "branch_available": branchAvailable,
        "alias": alias,
        "input_fields": List<dynamic>.from(inputFields.map((x) => x.toJson())),
        "url": url,
        "method": method,
      };
}

class InputField {
  String type;
  String name;
  String label;
  bool required;
  String placeHolder;
  List<Option> options;

  InputField({
    required this.type,
    required this.name,
    required this.label,
    required this.required,
    required this.placeHolder,
    required this.options,
  });

  factory InputField.fromJson(Map<String, dynamic> json) => InputField(
        type: json["type"],
        name: json["name"],
        label: json["label"],
        required: json["required"],
        placeHolder: json["place_holder"],
        options:
            List<Option>.from(json["options"].map((x) => Option.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "type": type,
        "name": name,
        "label": label,
        "required": required,
        "place_holder": placeHolder,
        "options": List<dynamic>.from(options.map((x) => x.toJson())),
      };
}

class Option implements DropdownModel {
  int id;
  dynamic code;
  String name;

  Option({
    required this.id,
    this.code,
    required this.name,
  });

  factory Option.fromJson(Map<String, dynamic> json) => Option(
        id: json["id"],
        code: json["code"] ?? "",
        name: json["name"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "code": code,
        "name": name,
      };

  @override
  String get title => name;
}

class PaymentInformations {
  String trx;
  String gatewayCurrencyName;
  String requestAmount;
  String exchangeRate;
  String conversionAmount;
  String totalCharge;
  String willGet;
  String payable;

  PaymentInformations({
    required this.trx,
    required this.gatewayCurrencyName,
    required this.requestAmount,
    required this.exchangeRate,
    required this.conversionAmount,
    required this.totalCharge,
    required this.willGet,
    required this.payable,
  });

  factory PaymentInformations.fromJson(Map<String, dynamic> json) =>
      PaymentInformations(
        trx: json["trx"],
        gatewayCurrencyName: json["gateway_currency_name"],
        requestAmount: json["request_amount"],
        exchangeRate: json["exchange_rate"],
        conversionAmount: json["conversion_amount"],
        totalCharge: json["total_charge"],
        willGet: json["will_get"],
        payable: json["payable"],
      );

  Map<String, dynamic> toJson() => {
        "trx": trx,
        "gateway_currency_name": gatewayCurrencyName,
        "request_amount": requestAmount,
        "exchange_rate": exchangeRate,
        "conversion_amount": conversionAmount,
        "total_charge": totalCharge,
        "will_get": willGet,
        "payable": payable,
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