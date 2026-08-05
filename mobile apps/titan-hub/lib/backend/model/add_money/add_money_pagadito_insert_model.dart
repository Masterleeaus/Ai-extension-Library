
import 'dart:convert';

AddMoneyPagaditoInsertModel addMoneyPagaditoInsertModelFromJson(String str) => AddMoneyPagaditoInsertModel.fromJson(json.decode(str));

String addMoneyPagaditoInsertModelToJson(AddMoneyPagaditoInsertModel data) => json.encode(data.toJson());

class AddMoneyPagaditoInsertModel {
    final Message message;
    final Data data;

    AddMoneyPagaditoInsertModel({
        required this.message,
        required this.data,
    });

    factory AddMoneyPagaditoInsertModel.fromJson(Map<String, dynamic> json) => AddMoneyPagaditoInsertModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
    );

    Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
    };
}

class Data {
    final String gatewayType;
    final String gatewayCurrencyName;
    final String alias;
    final String identify;
    final PaymentInformations paymentInformations;
    final String url;
    final String method;

    Data({
        required this.gatewayType,
        required this.gatewayCurrencyName,
        required this.alias,
        required this.identify,
        required this.paymentInformations,
        required this.url,
        required this.method,
    });

    factory Data.fromJson(Map<String, dynamic> json) => Data(
        gatewayType: json["gateway_type"],
        gatewayCurrencyName: json["gateway_currency_name"],
        alias: json["alias"],
        identify: json["identify"],
        paymentInformations: PaymentInformations.fromJson(json["payment_informations"]),
        url: json["url"],
        method: json["method"],
    );

    Map<String, dynamic> toJson() => {
        "gateway_type": gatewayType,
        "gateway_currency_name": gatewayCurrencyName,
        "alias": alias,
        "identify": identify,
        "payment_informations": paymentInformations.toJson(),
        "url": url,
        "method": method,
    };
}

class PaymentInformations {
    final String trx;
    final String gatewayCurrencyName;
    final String requestAmount;
    final String exchangeRate;
    final String totalCharge;
    final String willGet;
    final String payableAmount;

    PaymentInformations({
        required this.trx,
        required this.gatewayCurrencyName,
        required this.requestAmount,
        required this.exchangeRate,
        required this.totalCharge,
        required this.willGet,
        required this.payableAmount,
    });

    factory PaymentInformations.fromJson(Map<String, dynamic> json) => PaymentInformations(
        trx: json["trx"],
        gatewayCurrencyName: json["gateway_currency_name"],
        requestAmount: json["request_amount"],
        exchangeRate: json["exchange_rate"],
        totalCharge: json["total_charge"],
        willGet: json["will_get"],
        payableAmount: json["payable_amount"],
    );

    Map<String, dynamic> toJson() => {
        "trx": trx,
        "gateway_currency_name": gatewayCurrencyName,
        "request_amount": requestAmount,
        "exchange_rate": exchangeRate,
        "total_charge": totalCharge,
        "will_get": willGet,
        "payable_amount": payableAmount,
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
