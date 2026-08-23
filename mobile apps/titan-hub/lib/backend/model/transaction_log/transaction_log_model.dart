import 'dart:convert';

class TransactionLogModel {
  Message message;
  Data data;

  TransactionLogModel({
    required this.message,
    required this.data,
  });

  factory TransactionLogModel.fromRawJson(String str) =>
      TransactionLogModel.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory TransactionLogModel.fromJson(Map<String, dynamic> json) =>
      TransactionLogModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  TransactionTypes transactionTypes;
  Transactions transactions;

  Data({
    required this.transactionTypes,
    required this.transactions,
  });

  factory Data.fromRawJson(String str) => Data.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        transactionTypes: TransactionTypes.fromJson(json["transaction_types"]),
        transactions: Transactions.fromJson(json["transactions"]),
      );

  Map<String, dynamic> toJson() => {
        "transaction_types": transactionTypes.toJson(),
        "transactions": transactions.toJson(),
      };
}

class TransactionTypes {
  String addMoney;
  String moneyOut;
  String transferMoney;
  String billPay;
  String mobileTopUp;
  String virtualCard;
  String agentMoneyOut;
  String remittance;
  String merchantPayment;
  String makePayment;
  String addSubBalance;
  String payLink;
  String payUserPayLink;
  TransactionTypes({
    required this.addMoney,
    required this.moneyOut,
    required this.agentMoneyOut,
    required this.transferMoney,
    required this.billPay,
    required this.mobileTopUp,
    required this.virtualCard,
    required this.remittance,
    required this.merchantPayment,
    required this.makePayment,
    required this.addSubBalance,
    required this.payLink,
    required this.payUserPayLink,
  });

  factory TransactionTypes.fromRawJson(String str) =>
      TransactionTypes.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory TransactionTypes.fromJson(Map<String, dynamic> json) =>
      TransactionTypes(
        addMoney: json["add_money"] ?? '',
        moneyOut: json["money_out"] ?? '',
        transferMoney: json["transfer_money"] ?? '',
        agentMoneyOut: json["agent_money_out"] ?? '',
        billPay: json["bill_pay"] ?? '',
        mobileTopUp: json["mobile_top_up"] ?? '',
        virtualCard: json["virtual_card"] ?? '',
        remittance: json["remittance"] ?? '',
        merchantPayment: json["merchant-payment"] ?? '',
        makePayment: json["make_payment"] ?? '',
        addSubBalance: json["add_sub_balance"] ?? '',
        payLink: json["pay_link"] ?? '',
        payUserPayLink: json["pay_user_pay_link"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "add_money": addMoney,
        "money_out": moneyOut,
        "transfer_money": transferMoney,
        "agent_money_out": agentMoneyOut,
        "bill_pay": billPay,
        "mobile_top_up": mobileTopUp,
        "virtual_card": virtualCard,
        "remittance": remittance,
        "merchant-payment": merchantPayment,
        "make_payment": makePayment,
        "add_sub_balance": addSubBalance,
        "pay_link": payLink,
        "pay_user_pay_link": payUserPayLink,
      };
}

class Transactions {
  List<BillPay> billPay;
  List<MobileTopUp> mobileTopUp;
  List<AddMoney> addMoney;
  List<MoneyOut> moneyOut;
  List<AgentMoneyOut> agentMoneyOut;
  List<SendMoney> sendMoney;
  List<VirtualCard> virtualCard;
  List<Remittance> remittance;
  List<MerchantPayment> merchantPayment;
  List<MakePayment> makePayment;
  List<AddSubBalance> addSubBalance;
  List<PayPayLink> payLink;
  List<PayUserPayLink> payUserPayLink;
  Transactions({
    required this.billPay,
    required this.mobileTopUp,
    required this.addMoney,
    required this.moneyOut,
    required this.agentMoneyOut,
    required this.sendMoney,
    required this.virtualCard,
    required this.remittance,
    required this.merchantPayment,
    required this.makePayment,
    required this.addSubBalance,
    required this.payLink,
    required this.payUserPayLink,
  });

  factory Transactions.fromRawJson(String str) =>
      Transactions.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory Transactions.fromJson(Map<String, dynamic> json) => Transactions(
        billPay: List<BillPay>.from(
            json["bill_pay"].map((x) => BillPay.fromJson(x))),
        mobileTopUp: List<MobileTopUp>.from(
            json["mobile_top_up"].map((x) => MobileTopUp.fromJson(x))),
        addMoney: List<AddMoney>.from(
            json["add_money"].map((x) => AddMoney.fromJson(x))),
        moneyOut: List<MoneyOut>.from(
            json["money_out"].map((x) => MoneyOut.fromJson(x))),
        agentMoneyOut: List<AgentMoneyOut>.from(
            json["agent_money_out"].map((x) => AgentMoneyOut.fromJson(x))),
        sendMoney: List<SendMoney>.from(
            json["send_money"].map((x) => SendMoney.fromJson(x))),
        virtualCard: List<VirtualCard>.from(
            json["virtual_card"].map((x) => VirtualCard.fromJson(x))),
        remittance: List<Remittance>.from(
            json["remittance"].map((x) => Remittance.fromJson(x))),
        merchantPayment: List<MerchantPayment>.from(
            json["merchant_payment"].map((x) => MerchantPayment.fromJson(x))),
        makePayment: List<MakePayment>.from(
            json["make_payment"].map((x) => MakePayment.fromJson(x))),
        addSubBalance: List<AddSubBalance>.from(
            json["add_sub_balance"].map((x) => AddSubBalance.fromJson(x))),
        payLink: List<PayPayLink>.from(
            json["pay_link"].map((x) => PayPayLink.fromJson(x))),
        payUserPayLink: List<PayUserPayLink>.from(
            json["pay_user_pay_link"].map((x) => PayUserPayLink.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "bill_pay": List<dynamic>.from(billPay.map((x) => x.toJson())),
        "mobile_top_up": List<dynamic>.from(mobileTopUp.map((x) => x.toJson())),
        "add_money": List<dynamic>.from(addMoney.map((x) => x.toJson())),
        "money_out": List<dynamic>.from(moneyOut.map((x) => x.toJson())),
        "send_money": List<dynamic>.from(sendMoney.map((x) => x.toJson())),
        "virtual_card": List<dynamic>.from(virtualCard.map((x) => x.toJson())),
        "remittance": List<dynamic>.from(remittance.map((x) => x.toJson())),
        "merchant_payment":
            List<dynamic>.from(merchantPayment.map((x) => x.toJson())),
        "make_payment": List<dynamic>.from(makePayment.map((x) => x.toJson())),
        "add_sub_balance":
            List<dynamic>.from(addSubBalance.map((x) => x.toJson())),
        "pay_link": List<dynamic>.from(payLink.map((x) => x.toJson())),
        "pay_user_pay_link":
            List<dynamic>.from(payUserPayLink.map((x) => x.toJson())),
      };
}

class BillPay {
  int id;
  String trx;
  String transactionType;
  String requestAmount;
  String payable;
  String billType;
  String billNumber;
  String totalCharge;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;

  BillPay({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.billType,
    required this.billNumber,
    required this.totalCharge,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
  });

  factory BillPay.fromRawJson(String str) => BillPay.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory BillPay.fromJson(Map<String, dynamic> json) => BillPay(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        payable: json["payable"] ?? '',
        billType: json["bill_type"] ?? '',
        billNumber: json["bill_number"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "bill_type": billType,
        "bill_number": billNumber,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
      };
}

class MobileTopUp {
  int id;
  String trx;
  String transactionType;
  String requestAmount;
  String payable;
  String topupType;
  String mobileNumber;
  String totalCharge;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;

  MobileTopUp({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.topupType,
    required this.mobileNumber,
    required this.totalCharge,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
  });

  factory MobileTopUp.fromRawJson(String str) =>
      MobileTopUp.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory MobileTopUp.fromJson(Map<String, dynamic> json) => MobileTopUp(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        payable: json["payable"] ?? '',
        topupType: json["topup_type"] ?? '',
        mobileNumber: json["mobile_number"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "topup_type": topupType,
        "mobile_number": mobileNumber,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
      };
}

class AddMoney {
  int id;
  String trx;
  String gatewayName;
  String transactionType;
  String requestAmount;
  String payable;
  String exchangeRate;
  String totalCharge;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;
  bool confirm;
  dynamic confirmUrl;
  List<DynamicInput> dynamicInputs;

  AddMoney({
    required this.id,
    required this.trx,
    required this.gatewayName,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.exchangeRate,
    required this.totalCharge,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
    required this.confirm,
    required this.dynamicInputs,
    required this.confirmUrl,
  });

  factory AddMoney.fromRawJson(String str) =>
      AddMoney.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory AddMoney.fromJson(Map<String, dynamic> json) => AddMoney(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        gatewayName: json["gateway_name"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        payable: json["payable"] ?? '',
        exchangeRate: json["exchange_rate"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"] ?? '',
        confirm: json["confirm"] ?? false,
        confirmUrl: json["confirm_url"] ?? '',
        dynamicInputs: List<DynamicInput>.from(
            json["dynamic_inputs"].map((x) => DynamicInput.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "gateway_name": gatewayName,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "exchange_rate": exchangeRate,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
        "confirm": confirm,
        "dynamic_inputs":
            List<dynamic>.from(dynamicInputs.map((x) => x.toJson())),
      };
}

class MoneyOut {
  int id;
  String trx;
  String gatewayName;
  String gatewayCurrencyName;
  String transactionType;
  String requestAmount;
  String payable;
  String exchangeRate;
  String totalCharge;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;

  MoneyOut({
    required this.id,
    required this.trx,
    required this.gatewayName,
    required this.gatewayCurrencyName,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.exchangeRate,
    required this.totalCharge,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
  });

  factory MoneyOut.fromRawJson(String str) =>
      MoneyOut.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory MoneyOut.fromJson(Map<String, dynamic> json) => MoneyOut(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        gatewayName: json["gateway_name"] ?? '',
        gatewayCurrencyName: json["gateway_currency_name"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        payable: json["payable"] ?? '',
        exchangeRate: json["exchange_rate"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "gateway_name": gatewayName,
        "gateway_currency_name": gatewayCurrencyName,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "exchange_rate": exchangeRate,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
      };
}

class SendMoney {
  int id;
  String type;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String totalCharge;
  String payable;
  String recipientReceived;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;

  SendMoney({
    required this.id,
    required this.type,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.totalCharge,
    required this.payable,
    required this.recipientReceived,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory SendMoney.fromRawJson(String str) =>
      SendMoney.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory SendMoney.fromJson(Map<String, dynamic> json) => SendMoney(
        id: json["id"] ?? '',
        type: json["type"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        transactionHeading: json["transaction_heading"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        payable: json["payable"] ?? '',
        recipientReceived: json["recipient_received"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "total_charge": totalCharge,
        "payable": payable,
        "recipient_received": recipientReceived,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class AgentMoneyOut {
  int id;
  String type;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String totalCharge;
  String payable;
  String recipientReceived;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;

  AgentMoneyOut({
    required this.id,
    required this.type,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.totalCharge,
    required this.payable,
    required this.recipientReceived,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory AgentMoneyOut.fromRawJson(String str) =>
      AgentMoneyOut.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory AgentMoneyOut.fromJson(Map<String, dynamic> json) => AgentMoneyOut(
        id: json["id"] ?? '',
        type: json["type"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        transactionHeading: json["transaction_heading"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        payable: json["payable"] ?? '',
        recipientReceived: json["recipient_received"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "total_charge": totalCharge,
        "payable": payable,
        "recipient_received": recipientReceived,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class VirtualCard {
  int id;
  String trx;
  String transactionType;
  String requestAmount;
  String payable;
  String totalCharge;
  String cardAmount;
  String cardNumber;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;

  VirtualCard({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.totalCharge,
    required this.cardAmount,
    required this.cardNumber,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory VirtualCard.fromRawJson(String str) =>
      VirtualCard.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory VirtualCard.fromJson(Map<String, dynamic> json) => VirtualCard(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        payable: json["payable"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        cardAmount: json["card_amount"] ?? '',
        cardNumber: json["card_number"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "total_charge": totalCharge,
        "card_amount": cardAmount,
        "card_number": cardNumber,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class Remittance {
  int id;
  String type;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String totalCharge;
  String exchangeRate;
  String payable;
  String sendingCountry;
  String receivingCountry;
  String receipientName;
  String remittanceType;
  String remittanceTypeName;
  String receipientGet;
  String bankName;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;

  Remittance({
    required this.id,
    required this.type,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.totalCharge,
    required this.exchangeRate,
    required this.payable,
    required this.sendingCountry,
    required this.receivingCountry,
    required this.receipientName,
    required this.remittanceType,
    required this.remittanceTypeName,
    required this.receipientGet,
    required this.bankName,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
  });

  factory Remittance.fromRawJson(String str) =>
      Remittance.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory Remittance.fromJson(Map<String, dynamic> json) => Remittance(
        id: json["id"] ?? '',
        type: json["type"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        transactionHeading: json["transaction_heading"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        exchangeRate: json["exchange_rate"] ?? '',
        payable: json["payable"] ?? '',
        sendingCountry: json["sending_country"] ?? '',
        receivingCountry: json["receiving_country"] ?? '',
        receipientName: json["receipient_name"] ?? '',
        remittanceType: json["remittance_type"] ?? '',
        remittanceTypeName: json["remittance_type_name"] ?? '',
        receipientGet: json["receipient_get"] ?? '',
        bankName: json["bank_name"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "total_charge": totalCharge,
        "exchange_rate": exchangeRate,
        "payable": payable,
        "sending_country": sendingCountry,
        "receiving_country": receivingCountry,
        "receipient_name": receipientName,
        "remittance_type": remittanceType,
        "remittance_type_name": remittanceTypeName,
        "receipient_get": receipientGet,
        "bank_name": bankName,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
      };
}

class MakePayment {
  int id;
  String type;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String totalCharge;
  String payable;
  String recipientReceived;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;

  MakePayment({
    required this.id,
    required this.type,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.totalCharge,
    required this.payable,
    required this.recipientReceived,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory MakePayment.fromRawJson(String str) =>
      MakePayment.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory MakePayment.fromJson(Map<String, dynamic> json) => MakePayment(
        id: json["id"] ?? '',
        type: json["type"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        transactionHeading: json["transaction_heading"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        payable: json["payable"] ?? '',
        recipientReceived: json["recipient_received"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "total_charge": totalCharge,
        "payable": payable,
        "recipient_received": recipientReceived,
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class AddSubBalance {
  int id;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String currentBalance;
  String receiveAmount;
  dynamic deductedAmount;
  dynamic operationType;
  String exchangeRate;
  String totalCharge;
  String remark;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;

  AddSubBalance({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.currentBalance,
    required this.receiveAmount,
    this.deductedAmount,
    this.operationType,
    required this.exchangeRate,
    required this.totalCharge,
    required this.remark,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory AddSubBalance.fromRawJson(String str) =>
      AddSubBalance.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory AddSubBalance.fromJson(Map<String, dynamic> json) => AddSubBalance(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        transactionHeading: json["transaction_heading"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        currentBalance: json["current_balance"] ?? '',
        deductedAmount: json["deducted_amount"] ?? '',
        operationType: json["operation_type"] ?? '',
        receiveAmount: json["receive_amount"] ?? '',
        exchangeRate: json["exchange_rate"] ?? '',
        totalCharge: json["total_charge"] ?? '',
        remark: json["remark"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "deducted_amount": deductedAmount,
        "operation_type": operationType,
        "current_balance": currentBalance,
        "receive_amount": receiveAmount,
        "exchange_rate": exchangeRate,
        "total_charge": totalCharge,
        "remark": remark,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class StatusInfo {
  int success;
  int pending;
  int rejected;

  StatusInfo({
    required this.success,
    required this.pending,
    required this.rejected,
  });

  factory StatusInfo.fromRawJson(String str) =>
      StatusInfo.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory StatusInfo.fromJson(Map<String, dynamic> json) => StatusInfo(
        success: json["success"] ?? '',
        pending: json["pending"] ?? '',
        rejected: json["rejected"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "success": success,
        "pending": pending,
        "rejected": rejected,
      };
}

class MerchantPayment {
  int id;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  String payable;
  String envType;
  String senderAmount;
  String recipient;
  String recipientAmount;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;

  MerchantPayment({
    required this.id,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    required this.payable,
    required this.envType,
    required this.senderAmount,
    required this.recipient,
    required this.recipientAmount,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
  });

  factory MerchantPayment.fromRawJson(String str) =>
      MerchantPayment.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory MerchantPayment.fromJson(Map<String, dynamic> json) =>
      MerchantPayment(
        id: json["id"] ?? '',
        trx: json["trx"] ?? '',
        transactionType: json["transaction_type"] ?? '',
        transactionHeading: json["transaction_heading"] ?? '',
        requestAmount: json["request_amount"] ?? '',
        payable: json["payable"] ?? '',
        envType: json["env_type"] ?? '',
        senderAmount: json["sender_amount"] ?? '',
        recipient: json["recipient"] ?? '',
        recipientAmount: json["recipient_amount"] ?? '',
        status: json["status"] ?? '',
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transaction_type": transactionType,
        "transaction_heading": transactionHeading,
        "request_amount": requestAmount,
        "payable": payable,
        "env_type": envType,
        "sender_amount": senderAmount,
        "recipient": recipient,
        "recipient_amount": recipientAmount,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
      };
}

class Message {
  List<String> success;

  Message({
    required this.success,
  });

  factory Message.fromRawJson(String str) => Message.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        success: List<String>.from(json["success"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "success": List<dynamic>.from(success.map((x) => x)),
      };
}

class DynamicInput {
  String type;
  String label;
  String placeholder;
  String name;
  bool required;
  Validation validation;

  DynamicInput({
    required this.type,
    required this.label,
    required this.placeholder,
    required this.name,
    required this.required,
    required this.validation,
  });

  factory DynamicInput.fromJson(Map<String, dynamic> json) => DynamicInput(
        type: json["type"],
        label: json["label"],
        placeholder: json["placeholder"],
        name: json["name"],
        required: json["required"],
        validation: Validation.fromJson(json["validation"]),
      );

  Map<String, dynamic> toJson() => {
        "type": type,
        "label": label,
        "placeholder": placeholder,
        "name": name,
        "required": required,
        "validation": validation.toJson(),
      };
}

class Validation {
  String min;
  String max;
  bool required;

  Validation({
    required this.min,
    required this.max,
    required this.required,
  });

  factory Validation.fromJson(Map<String, dynamic> json) => Validation(
        min: json["min"],
        max: json["max"],
        required: json["required"],
      );

  Map<String, dynamic> toJson() => {
        "min": min,
        "max": max,
        "required": required,
      };
}

class PayPayLink {
  final int id;
  final String trx;
  final String title;
  final String transactionType;
  final String requestAmount;
  final String payable;
  final String exchangeRate;
  final String totalCharge;
  final String currentBalance;
  final String paymentType;
  final PaymentTypeGatewayData paymentTypeGatewayData;
  final PaymentTypeCardData paymentTypeCardData;
  final PaymentTypeWalletData paymentTypeWalletData;
  final int statusValue;
  final String status;
  final DateTime dateTime;
  final StatusInfo statusInfo;

  PayPayLink({
    required this.id,
    required this.trx,
    required this.title,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.exchangeRate,
    required this.totalCharge,
    required this.currentBalance,
    required this.paymentType,
    required this.paymentTypeGatewayData,
    required this.paymentTypeCardData,
    required this.paymentTypeWalletData,
    required this.statusValue,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory PayPayLink.fromJson(Map<String, dynamic> json) => PayPayLink(
        id: json["id"],
        trx: json["trx"],
        title: json["title"],
        transactionType: json["transaction_type"],
        requestAmount: json["request_amount"],
        payable: json["payable"],
        exchangeRate: json["exchange_rate"],
        totalCharge: json["total_charge"],
        currentBalance: json["current_balance"],
        paymentType: json["payment_type"],
        paymentTypeGatewayData:
            PaymentTypeGatewayData.fromJson(json["payment_type_gateway_data"]),
        paymentTypeCardData:
            PaymentTypeCardData.fromJson(json["payment_type_card_data"]),
        paymentTypeWalletData:
            PaymentTypeWalletData.fromJson(json["payment_type_wallet_data"]),
        statusValue: json["status_value"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "title": title,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "exchange_rate": exchangeRate,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "payment_type": paymentType,
        "payment_type_gateway_data": paymentTypeGatewayData.toJson(),
        "payment_type_card_data": paymentTypeCardData.toJson(),
        "payment_type_wallet_data": paymentTypeWalletData.toJson(),
        "status_value": statusValue,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}

class PaymentTypeCardData {
  final String senderEmail;
  final String cardHolderName;
  final String senderCardLast4;

  PaymentTypeCardData({
    required this.senderEmail,
    required this.cardHolderName,
    required this.senderCardLast4,
  });

  factory PaymentTypeCardData.fromJson(Map<String, dynamic> json) =>
      PaymentTypeCardData(
        senderEmail: json["sender_email"],
        cardHolderName: json["card_holder_name"],
        senderCardLast4: json["sender_card_last4"],
      );

  Map<String, dynamic> toJson() => {
        "sender_email": senderEmail,
        "card_holder_name": cardHolderName,
        "sender_card_last4": senderCardLast4,
      };
}

class PaymentTypeGatewayData {
  final String paymentGateway;

  PaymentTypeGatewayData({
    required this.paymentGateway,
  });

  factory PaymentTypeGatewayData.fromJson(Map<String, dynamic> json) =>
      PaymentTypeGatewayData(
        paymentGateway: json["payment_gateway"],
      );

  Map<String, dynamic> toJson() => {
        "payment_gateway": paymentGateway,
      };
}

class PaymentTypeWalletData {
  final String senderEmail;

  PaymentTypeWalletData({
    required this.senderEmail,
  });

  factory PaymentTypeWalletData.fromJson(Map<String, dynamic> json) =>
      PaymentTypeWalletData(
        senderEmail: json["sender_email"],
      );

  Map<String, dynamic> toJson() => {
        "sender_email": senderEmail,
      };
}

class PayUserPayLink {
  final int id;
  final String trx;
  final String title;
  final String transactionType;
  final String requestAmount;
  final String payable;
  final String exchangeRate;
  final String totalCharge;
  final String currentBalance;
  final String paymentType;
  final int statusValue;
  final String status;
  final DateTime dateTime;
  final StatusInfo statusInfo;

  PayUserPayLink({
    required this.id,
    required this.trx,
    required this.title,
    required this.transactionType,
    required this.requestAmount,
    required this.payable,
    required this.exchangeRate,
    required this.totalCharge,
    required this.currentBalance,
    required this.paymentType,
    required this.statusValue,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
  });

  factory PayUserPayLink.fromJson(Map<String, dynamic> json) => PayUserPayLink(
        id: json["id"],
        trx: json["trx"],
        title: json["title"],
        transactionType: json["transaction_type"],
        requestAmount: json["request_amount"],
        payable: json["payable"],
        exchangeRate: json["exchange_rate"],
        totalCharge: json["total_charge"],
        currentBalance: json["current_balance"],
        paymentType: json["payment_type"],
        statusValue: json["status_value"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "title": title,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "exchange_rate": exchangeRate,
        "total_charge": totalCharge,
        "current_balance": currentBalance,
        "payment_type": paymentType,
        "status_value": statusValue,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
      };
}
