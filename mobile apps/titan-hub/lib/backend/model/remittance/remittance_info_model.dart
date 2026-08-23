import 'package:qrpay/widgets/dropdown/custom_dropdown_menu.dart';

class RemittanceInfoModel {
  Message message;
  Data data;

  RemittanceInfoModel({
    required this.message,
    required this.data,
  });

  factory RemittanceInfoModel.fromJson(Map<String, dynamic> json) =>
      RemittanceInfoModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String fromCountryFlugPath;
  String toCountryFlugPath;
  GetRemainingFields getRemainingFields;

  String defaultImage;
  UserWallet userWallet;
  List<TransactionType> transactionTypes;
  RemittanceCharge remittanceCharge;
  List<Country> fromCountry;
  List<Country> toCountries;
  List<Recipient> recipients;
  List<Transaction> transactions;

  Data({
    required this.fromCountryFlugPath,
    required this.toCountryFlugPath,
    required this.getRemainingFields,
    required this.defaultImage,
    required this.userWallet,
    required this.transactionTypes,
    required this.remittanceCharge,
    required this.fromCountry,
    required this.toCountries,
    required this.recipients,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        fromCountryFlugPath: json["fromCountryFlugPath"],
        toCountryFlugPath: json["toCountryFlugPath"],
        getRemainingFields:
            GetRemainingFields.fromJson(json["get_remaining_fields"]),
        defaultImage: json["default_image"],
        userWallet: UserWallet.fromJson(json["userWallet"]),
        transactionTypes: List<TransactionType>.from(
            json["transactionTypes"].map((x) => TransactionType.fromJson(x))),
        remittanceCharge: RemittanceCharge.fromJson(json["remittanceCharge"]),
        fromCountry: List<Country>.from(
            json["fromCountry"].map((x) => Country.fromJson(x))),
        toCountries: List<Country>.from(
            json["toCountries"].map((x) => Country.fromJson(x))),
        recipients: List<Recipient>.from(
            json["recipients"].map((x) => Recipient.fromJson(x))),
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "fromCountryFlugPath": fromCountryFlugPath,
        "toCountryFlugPath": toCountryFlugPath,
        "get_remaining_fields": getRemainingFields.toJson(),
        "default_image": defaultImage,
        "userWallet": userWallet.toJson(),
        "transactionTypes":
            List<dynamic>.from(transactionTypes.map((x) => x.toJson())),
        "remittanceCharge": remittanceCharge.toJson(),
        "fromCountry": List<dynamic>.from(fromCountry.map((x) => x.toJson())),
        "toCountries": List<dynamic>.from(toCountries.map((x) => x.toJson())),
        "recipients": List<dynamic>.from(recipients.map((x) => x.toJson())),
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
      };
}

class GetRemainingFields {
  String transactionType;
  String attribute;

  GetRemainingFields({
    required this.transactionType,
    required this.attribute,
  });

  factory GetRemainingFields.fromJson(Map<String, dynamic> json) =>
      GetRemainingFields(
        transactionType: json["transaction_type"],
        attribute: json["attribute"],
      );

  Map<String, dynamic> toJson() => {
        "transaction_type": transactionType,
        "attribute": attribute,
      };
}

class Country implements DropdownMenuModel {
  int id;
  String country;
  String name;
  String code;
  String symbol;
  dynamic flag;
  double rate;
  int status;
  DateTime createdAt;
  DateTime updatedAt;
  String? mobileCode;

  Country({
    required this.id,
    required this.country,
    required this.name,
    required this.code,
    required this.symbol,
    this.flag,
    required this.rate,
    required this.status,
    required this.createdAt,
    required this.updatedAt,
    this.mobileCode,
  });

  factory Country.fromJson(Map<String, dynamic> json) => Country(
        id: json["id"],
        country: json["country"],
        name: json["name"],
        code: json["code"],
        symbol: json["symbol"],
        flag: json["flag"] ?? '',
        rate: json["rate"]?.toDouble(),
        status: json["status"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        mobileCode: json["mobile_code"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "country": country,
        "name": name,
        "code": code,
        "symbol": symbol,
        "flag": flag,
        "rate": rate,
        "status": status,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "mobile_code": mobileCode,
      };

  @override
  String get title => country;
}

class Recipient {
  int id;
  int country;
  String countryName;
  String trxType;
  String trxTypeName;
  String alias;
  String firstname;
  String lastname;
  String mobileCode;
  String mobile;
  String city;
  String state;
  String address;
  String zipCode;
  DateTime createdAt;
  DateTime updatedAt;

  Recipient({
    required this.id,
    required this.country,
    required this.countryName,
    required this.trxType,
    required this.trxTypeName,
    required this.alias,
    required this.firstname,
    required this.lastname,
    required this.mobileCode,
    required this.mobile,
    required this.city,
    required this.state,
    required this.address,
    required this.zipCode,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Recipient.fromJson(Map<String, dynamic> json) => Recipient(
        id: json["id"],
        country: json["country"],
        countryName: json["country_name"],
        trxType: json["trx_type"],
        trxTypeName: json["trx_type_name"],
        alias: json["alias"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        mobileCode: json["mobile_code"],
        mobile: json["mobile"],
        city: json["city"],
        state: json["state"],
        address: json["address"],
        zipCode: json["zip_code"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "country": country,
        "country_name": countryName,
        "trx_type": trxType,
        "trx_type_name": trxTypeName,
        "alias": alias,
        "firstname": firstname,
        "lastname": lastname,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "city": city,
        "state": state,
        "address": address,
        "zip_code": zipCode,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
      };
}

class RemittanceCharge {
  int id;
  String slug;
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;
  dynamic monthlyLimit;
  dynamic dailyLimit;

  RemittanceCharge({
    required this.id,
    required this.slug,
    required this.title,
    this.fixedCharge,
    this.percentCharge,
    this.minLimit,
    this.maxLimit,
    this.monthlyLimit,
    this.dailyLimit,
  });

  factory RemittanceCharge.fromJson(Map<String, dynamic> json) =>
      RemittanceCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"]?.toDouble() ?? 0.0,
        percentCharge: json["percent_charge"]?.toDouble() ?? 0.0,
        minLimit: json["min_limit"]?.toDouble() ?? 0.0,
        maxLimit: json["max_limit"]?.toDouble() ?? 0.0,
        monthlyLimit: json["monthly_limit"]?.toDouble() ?? 0.0,
        dailyLimit: json["daily_limit"]?.toDouble() ?? 0.0,
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "slug": slug,
        "title": title,
        "fixed_charge": fixedCharge,
        "percent_charge": percentCharge,
        "min_limit": minLimit,
        "max_limit": maxLimit,
        "monthly_limit": monthlyLimit,
        "daily_limit": dailyLimit,
      };
}

class TransactionType implements DropdownMenuModel {
  int id;
  String fieldName;
  String labelName;

  TransactionType({
    required this.id,
    required this.fieldName,
    required this.labelName,
  });

  factory TransactionType.fromJson(Map<String, dynamic> json) =>
      TransactionType(
        id: json["id"],
        fieldName: json["field_name"],
        labelName: json["label_name"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "field_name": fieldName,
        "label_name": labelName,
      };

  @override
  String get title => labelName;
}

class Transaction {
  int id;
  String type;
  String trx;
  String transactionType;
  String transactionHeading;
  String requestAmount;
  dynamic totalCharge;
  dynamic exchangeRate;
  dynamic payable;
  String sendingCountry;
  String receivingCountry;
  dynamic receipientName;
  String remittanceType;
  String remittanceTypeName;
  dynamic receipientGet;
  String currentBalance;
  String status;
  DateTime dateTime;
  StatusInfo statusInfo;
  String rejectionReason;
  // String? pickupPoint;

  Transaction({
    required this.id,
    required this.type,
    required this.trx,
    required this.transactionType,
    required this.transactionHeading,
    required this.requestAmount,
    this.totalCharge,
    this.exchangeRate,
    this.payable,
    required this.sendingCountry,
    required this.receivingCountry,
    required this.receipientName,
    required this.remittanceType,
    required this.remittanceTypeName,
    required this.receipientGet,
    required this.currentBalance,
    required this.status,
    required this.dateTime,
    required this.statusInfo,
    required this.rejectionReason,
    // this.pickupPoint,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        type: json["type"],
        trx: json["trx"],
        transactionType: json["transaction_type"],
        transactionHeading: json["transaction_heading"],
        requestAmount: json["request_amount"],
        totalCharge: json["total_charge"],
        exchangeRate: json["exchange_rate"],
        payable: json["payable"],
        sendingCountry: json["sending_country"],
        receivingCountry: json["receiving_country"],
        receipientName: json["receipient_name"],
        remittanceType: json["remittance_type"],
        remittanceTypeName: json["remittance_type_name"],
        receipientGet: json["receipient_get"],
        currentBalance: json["current_balance"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: StatusInfo.fromJson(json["status_info"]),
        rejectionReason: json["rejection_reason"],
        // pickupPoint: json["pickup_point"],
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
        "current_balance": currentBalance,
        "status": status,
        "date_time": dateTime.toIso8601String(),
        "status_info": statusInfo.toJson(),
        "rejection_reason": rejectionReason,
        // "pickup_point": pickupPoint,
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

  factory StatusInfo.fromJson(Map<String, dynamic> json) => StatusInfo(
        success: json["success"],
        pending: json["pending"],
        rejected: json["rejected"],
      );

  Map<String, dynamic> toJson() => {
        "success": success,
        "pending": pending,
        "rejected": rejected,
      };
}

class UserWallet {
  double balance;
  String currency;

  UserWallet({
    required this.balance,
    required this.currency,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"]?.toDouble(),
        currency: json["currency"],
      );

  Map<String, dynamic> toJson() => {
        "balance": balance,
        "currency": currency,
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
