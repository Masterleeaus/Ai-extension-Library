import 'dart:convert';

DashboardModel dashboardModelFromJson(String str) =>
    DashboardModel.fromJson(json.decode(str));

String dashboardModelToJson(DashboardModel data) => json.encode(data.toJson());

class DashboardModel {
  Message message;
  Data data;

  DashboardModel({
    required this.message,
    required this.data,
  });

  factory DashboardModel.fromJson(Map<String, dynamic> json) => DashboardModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  final PusherCredentials pusherCredentials;
  final CardCharge cardReloadCharge;
  dynamic baseCurr;
  ModuleAccess moduleAccess;
  String? activeVirtualSystem;
  UserWallet userWallet;
  dynamic defaultImage;
  dynamic imagePath;
  User user;
  dynamic totalAddMoney;
  dynamic totalReceiveRemittance;
  dynamic totalSendRemittance;
  dynamic cardAmount;
  dynamic billPay;
  dynamic topUps;
  dynamic totalTransactions;
  List<Transaction> transactions;

  Data({
    required this.pusherCredentials,
    required this.cardReloadCharge,
    required this.baseCurr,
    required this.moduleAccess,
    required this.activeVirtualSystem,
    required this.userWallet,
    required this.defaultImage,
    required this.imagePath,
    required this.user,
    required this.totalAddMoney,
    required this.totalReceiveRemittance,
    required this.totalSendRemittance,
    required this.cardAmount,
    required this.billPay,
    required this.topUps,
    required this.totalTransactions,
    required this.transactions,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        pusherCredentials:
            PusherCredentials.fromJson(json["pusher_credentials"]),
        cardReloadCharge: CardCharge.fromJson(json["card_reload_charge"]),
        baseCurr: json["base_curr"] ?? "",
        moduleAccess: ModuleAccess.fromJson(json["module_access"]),
        activeVirtualSystem: json["active_virtual_system"],
        userWallet: UserWallet.fromJson(json["userWallet"]),
        defaultImage: json["default_image"] ?? '',
        imagePath: json["image_path"] ?? '',
        user: User.fromJson(json["user"]),
        totalAddMoney: json["totalAddMoney"] ?? '',
        totalReceiveRemittance: json["totalReceiveRemittance"] ?? '',
        totalSendRemittance: json["totalSendRemittance"] ?? '',
        cardAmount: json["cardAmount"] ?? '',
        billPay: json["billPay"] ?? '',
        topUps: json["topUps"] ?? '',
        totalTransactions: json["totalTransactions"] ?? '',
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "pusher_credentials": pusherCredentials.toJson(),
        "card_reload_charge": cardReloadCharge.toJson(),
        "base_curr": baseCurr,
        "module_access": moduleAccess.toJson(),
        "active_virtual_system": activeVirtualSystem,
        "userWallet": userWallet.toJson(),
        "default_image": defaultImage,
        "image_path": imagePath,
        "user": user.toJson(),
        "totalAddMoney": totalAddMoney,
        "totalReceiveRemittance": totalReceiveRemittance,
        "totalSendRemittance": totalSendRemittance,
        "cardAmount": cardAmount,
        "billPay": billPay,
        "topUps": topUps,
        "totalTransactions": totalTransactions,
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
      };
}

class ModuleAccess {
  bool sendMoney;
  bool receiveMoney;
  bool remittanceMoney;
  bool addMoney;
  bool withdrawMoney;
  bool makePayment;
  bool virtualCard;
  bool billPay;
  bool mobileTopUp;
  bool requestMoney;
  bool payLink;

  ModuleAccess({
    required this.sendMoney,
    required this.receiveMoney,
    required this.remittanceMoney,
    required this.addMoney,
    required this.withdrawMoney,
    required this.makePayment,
    required this.virtualCard,
    required this.billPay,
    required this.mobileTopUp,
    required this.requestMoney,
    required this.payLink,
  });

  factory ModuleAccess.fromJson(Map<String, dynamic> json) => ModuleAccess(
        sendMoney: json["send_money"] ?? true,
        receiveMoney: json["receive_money"] ?? true,
        remittanceMoney: json["remittance_money"] ?? true,
        addMoney: json["add_money"] ?? true,
        withdrawMoney: json["withdraw_money"] ?? true,
        makePayment: json["make_payment"] ?? true,
        virtualCard: json["virtual_card"] ?? true,
        billPay: json["bill_pay"] ?? true,
        mobileTopUp: json["mobile_top_up"] ?? true,
        requestMoney: json["request_money"] ?? true,
        payLink: json["pay_link"] ?? true,
      );

  Map<String, dynamic> toJson() => {
        "send_money": sendMoney,
        "receive_money": receiveMoney,
        "remittance_money": remittanceMoney,
        "add_money": addMoney,
        "withdraw_money": withdrawMoney,
        "make_payment": makePayment,
        "virtual_card": virtualCard,
        "bill_pay": billPay,
        "mobile_top_up": mobileTopUp,
        "request_money": requestMoney,
        "pay_link": payLink,
      };
}

class Transaction {
  int? id;
  String? type;
  String? trx;
  String? transactionType;
  String? requestAmount;
  String? payable;
  String? status;
  String? remark;
  DateTime? dateTime;

  Transaction({
    this.id,
    this.type,
    this.trx,
    this.transactionType,
    this.requestAmount,
    this.payable,
    this.status,
    this.remark,
    this.dateTime,
  });

  factory Transaction.fromRawJson(String str) =>
      Transaction.fromJson(json.decode(str));

  String toRawJson() => json.encode(toJson());

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        type: json["type"],
        trx: json["trx"],
        transactionType: json["transaction_type"],
        requestAmount: json["request_amount"],
        payable: json["payable"],
        status: json["status"],
        remark: json["remark"],
        dateTime: json["date_time"] == null
            ? null
            : DateTime.parse(json["date_time"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "type": type,
        "trx": trx,
        "transaction_type": transactionType,
        "request_amount": requestAmount,
        "payable": payable,
        "status": status,
        "remark": remark,
        "date_time": dateTime?.toIso8601String(),
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

class User {
  int id;
  dynamic firstname;
  dynamic lastname;
  dynamic username;
  dynamic email;
  dynamic mobileCode;
  dynamic mobile;
  dynamic fullMobile;
  dynamic refferalUserId;
  dynamic image;
  int status;
  Address address;
  int emailVerified;
  int smsVerified;
  int kycVerified;
  dynamic verCode;
  dynamic verCodeSendAt;
  int twoFactorVerified;
  int twoFactorStatus;
  dynamic twoFactorSecret;
  dynamic deviceId;
  dynamic firebaseToken;
  dynamic emailVerifiedAt;
  dynamic deletedAt;
  DateTime createdAt;
  DateTime updatedAt;
  dynamic fullname;
  dynamic userImage;
  StringStatus stringStatus;
  dynamic lastLogin;
  StringStatus kycStringStatus;

  User({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.username,
    required this.email,
    required this.mobileCode,
    required this.mobile,
    required this.fullMobile,
    this.refferalUserId,
    required this.image,
    required this.status,
    required this.address,
    required this.emailVerified,
    required this.smsVerified,
    required this.kycVerified,
    this.verCode,
    this.verCodeSendAt,
    required this.twoFactorVerified,
    required this.twoFactorStatus,
    required this.twoFactorSecret,
    this.deviceId,
    this.firebaseToken,
    this.emailVerifiedAt,
    this.deletedAt,
    required this.createdAt,
    required this.updatedAt,
    required this.fullname,
    required this.userImage,
    required this.stringStatus,
    required this.lastLogin,
    required this.kycStringStatus,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json["id"] ?? '',
        firstname: json["firstname"] ?? '',
        lastname: json["lastname"] ?? '',
        username: json["username"] ?? '',
        email: json["email"] ?? '',
        mobileCode: json["mobile_code"] ?? '',
        mobile: json["mobile"] ?? '',
        fullMobile: json["full_mobile"] ?? '',
        refferalUserId: json["refferal_user_id"] ?? '',
        image: json["image"] ?? '',
        status: json["status"] ?? '',
        address: Address.fromJson(json["address"]),
        emailVerified: json["email_verified"] ?? '',
        smsVerified: json["sms_verified"] ?? '',
        kycVerified: json["kyc_verified"] ?? '',
        verCode: json["ver_code"] ?? '',
        verCodeSendAt: json["ver_code_send_at"] ?? '',
        twoFactorVerified: json["two_factor_verified"] ?? '',
        twoFactorStatus: json["two_factor_status"] ?? '',
        twoFactorSecret: json["two_factor_secret"] ?? '',
        deviceId: json["device_id"] ?? '',
        firebaseToken: json["firebase_token"] ?? '',
        emailVerifiedAt: json["email_verified_at"] ?? '',
        deletedAt: json["deleted_at"] ?? '',
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        fullname: json["fullname"] ?? '',
        userImage: json["userImage"] ?? '',
        stringStatus: StringStatus.fromJson(json["stringStatus"]),
        lastLogin: json["lastLogin"] ?? '',
        kycStringStatus: StringStatus.fromJson(json["kycStringStatus"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "firstname": firstname,
        "lastname": lastname,
        "username": username,
        "email": email,
        "mobile_code": mobileCode,
        "mobile": mobile,
        "full_mobile": fullMobile,
        "refferal_user_id": refferalUserId,
        "image": image,
        "status": status,
        "address": address.toJson(),
        "email_verified": emailVerified,
        "sms_verified": smsVerified,
        "kyc_verified": kycVerified,
        "ver_code": verCode,
        "ver_code_send_at": verCodeSendAt,
        "two_factor_verified": twoFactorVerified,
        "two_factor_status": twoFactorStatus,
        "two_factor_secret": twoFactorSecret,
        "device_id": deviceId,
        "firebase_token": firebaseToken,
        "email_verified_at": emailVerifiedAt,
        "deleted_at": deletedAt,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "fullname": fullname,
        "userImage": userImage,
        "stringStatus": stringStatus.toJson(),
        "lastLogin": lastLogin,
        "kycStringStatus": kycStringStatus.toJson(),
      };
}

class Address {
  dynamic country;
  dynamic state;
  dynamic city;
  dynamic zip;
  dynamic address;

  Address({
    required this.country,
    required this.state,
    required this.city,
    required this.zip,
    required this.address,
  });

  factory Address.fromJson(Map<String, dynamic> json) => Address(
        country: json["country"] ?? '',
        state: json["state"] ?? '',
        city: json["city"] ?? '',
        zip: json["zip"] ?? '',
        address: json["address"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "country": country,
        "state": state,
        "city": city,
        "zip": zip,
        "address": address,
      };
}

class StringStatus {
  dynamic stringStatusClass;
  dynamic value;

  StringStatus({
    required this.stringStatusClass,
    required this.value,
  });

  factory StringStatus.fromJson(Map<String, dynamic> json) => StringStatus(
        stringStatusClass: json["class"] ?? '',
        value: json["value"] ?? '',
      );

  Map<String, dynamic> toJson() => {
        "class": stringStatusClass,
        "value": value,
      };
}

class UserWallet {
  dynamic balance;
  dynamic currency;

  UserWallet({
    required this.balance,
    required this.currency,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"]?.toDouble(),
        currency: json["currency"] ?? '',
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

class CardCharge {
  final int id;
  final String slug;
  final String title;
  final dynamic fixedCharge;
  final dynamic percentCharge;
  final dynamic minLimit;
  final dynamic maxLimit;

  CardCharge({
    required this.id,
    required this.slug,
    required this.title,
    this.fixedCharge,
    this.percentCharge,
    this.minLimit,
    this.maxLimit,
  });

  factory CardCharge.fromJson(Map<String, dynamic> json) => CardCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"]?.toDouble() ?? 0.0,
        percentCharge: json["percent_charge"]?.toDouble() ?? 0.0,
        minLimit: json["min_limit"]?.toDouble() ?? 0.0,
        maxLimit: json["max_limit"]?.toDouble() ?? 0.0,
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

class PusherCredentials {
  final String instanceId;
  final String secretKey;

  PusherCredentials({
    required this.instanceId,
    required this.secretKey,
  });

  factory PusherCredentials.fromJson(Map<String, dynamic> json) =>
      PusherCredentials(
        instanceId: json["instanceId"],
        secretKey: json["secretKey"],
      );

  Map<String, dynamic> toJson() => {
        "instanceId": instanceId,
        "secretKey": secretKey,
      };
}
