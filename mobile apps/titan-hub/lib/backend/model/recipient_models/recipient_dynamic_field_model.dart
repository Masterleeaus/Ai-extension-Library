// To parse this JSON data, do
//
//     final recipientDynamicFieldModel = recipientDynamicFieldModelFromJson(jsonString);

import 'dart:convert';

RecipientDynamicFieldModel recipientDynamicFieldModelFromJson(String str) => RecipientDynamicFieldModel.fromJson(json.decode(str));

String recipientDynamicFieldModelToJson(RecipientDynamicFieldModel data) => json.encode(data.toJson());

class RecipientDynamicFieldModel {
  Message message;
  Data data;

  RecipientDynamicFieldModel({
    required this.message,
    required this.data,
  });

  factory RecipientDynamicFieldModel.fromJson(Map<String, dynamic> json) => RecipientDynamicFieldModel(
    message: Message.fromJson(json["message"]),
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message.toJson(),
    "data": data.toJson(),
  };
}

class Data {
  List<BankTransfer> bankTransfer;
  List<BankTransfer> walletToWalletTransfer;
  List<BankTransfer> cashPickup;

  Data({
    required this.bankTransfer,
    required this.walletToWalletTransfer,
    required this.cashPickup,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    bankTransfer: List<BankTransfer>.from(json["bank-transfer"].map((x) => BankTransfer.fromJson(x))),
    walletToWalletTransfer: List<BankTransfer>.from(json["wallet-to-wallet-transfer"].map((x) => BankTransfer.fromJson(x))),
    cashPickup: List<BankTransfer>.from(json["cash-pickup"].map((x) => BankTransfer.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "bank-transfer": List<dynamic>.from(bankTransfer.map((x) => x.toJson())),
    "wallet-to-wallet-transfer": List<dynamic>.from(walletToWalletTransfer.map((x) => x.toJson())),
    "cash-pickup": List<dynamic>.from(cashPickup.map((x) => x.toJson())),
  };
}

class BankTransfer {
  String fieldName;
  String labelName;

  BankTransfer({
    required this.fieldName,
    required this.labelName,
  });

  factory BankTransfer.fromJson(Map<String, dynamic> json) => BankTransfer(
    fieldName: json["field_name"],
    labelName: json["label_name"],
  );

  Map<String, dynamic> toJson() => {
    "field_name": fieldName,
    "label_name": labelName,
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
