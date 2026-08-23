class StrowalletCardModel {
  Message message;
  Data data;

  StrowalletCardModel({
    required this.message,
    required this.data,
  });

  factory StrowalletCardModel.fromJson(Map<String, dynamic> json) =>
      StrowalletCardModel(
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
  CardBasicInfo cardBasicInfo;
  List<MyCard> myCards;
  User user;
  UserWallet userWallet;
  CardCharge cardCharge;
  List<Transaction> transactions;
  bool cardCreateAction;

  Data({
    required this.baseCurr,
    required this.cardBasicInfo,
    required this.myCards,
    required this.user,
    required this.userWallet,
    required this.cardCharge,
    required this.transactions,
    required this.cardCreateAction,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        cardBasicInfo: CardBasicInfo.fromJson(json["card_basic_info"]),
        myCards:
            List<MyCard>.from(json["myCards"].map((x) => MyCard.fromJson(x))),
        user: User.fromJson(json["user"]),
        userWallet: UserWallet.fromJson(json["userWallet"]),
        cardCharge: CardCharge.fromJson(json["cardCharge"]),
        transactions: List<Transaction>.from(
            json["transactions"].map((x) => Transaction.fromJson(x))),
        cardCreateAction: json['card_create_action'] ?? false,
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "card_basic_info": cardBasicInfo.toJson(),
        "myCards": List<dynamic>.from(myCards.map((x) => x.toJson())),
        "user": user.toJson(),
        "userWallet": userWallet.toJson(),
        "cardCharge": cardCharge.toJson(),
        "transactions": List<dynamic>.from(transactions.map((x) => x.toJson())),
        "card_create_action": cardCreateAction,
      };
}

class CardBasicInfo {
  int cardCreateLimit;
  String cardBackDetails;
  String cardBg;
  String siteTitle;
  String siteLogo;
  String siteFav;

  CardBasicInfo({
    required this.cardCreateLimit,
    required this.cardBackDetails,
    required this.cardBg,
    required this.siteTitle,
    required this.siteLogo,
    required this.siteFav,
  });

  factory CardBasicInfo.fromJson(Map<String, dynamic> json) => CardBasicInfo(
        cardCreateLimit: json["card_create_limit"],
        cardBackDetails: json["card_back_details"],
        cardBg: json["card_bg"],
        siteTitle: json["site_title"],
        siteLogo: json["site_logo"],
        siteFav: json["site_fav"],
      );

  Map<String, dynamic> toJson() => {
        "card_create_limit": cardCreateLimit,
        "card_back_details": cardBackDetails,
        "card_bg": cardBg,
        "site_title": siteTitle,
        "site_logo": siteLogo,
        "site_fav": siteFav,
      };
}

class CardCharge {
  int id;
  String slug;
  String title;
  dynamic fixedCharge;
  dynamic percentCharge;
  dynamic minLimit;
  dynamic maxLimit;

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

class MyCard {
  int id;
  String name;
  String cardId;
  String cardNumber;
  String expiry;
  String cvv;
  dynamic balance;
  String cardBackDetails;
  String siteTitle;
  String siteLogo;
  String siteFav;
  bool status;
  dynamic isDefault;
  MyCardStatusInfo statusInfo;

  MyCard({
    required this.id,
    required this.name,
    required this.cardId,
    required this.cardNumber,
    required this.expiry,
    required this.cvv,
    required this.balance,
    required this.cardBackDetails,
    required this.siteTitle,
    required this.siteLogo,
    required this.siteFav,
    required this.status,
    required this.statusInfo,
    required this.isDefault,
  });

  factory MyCard.fromJson(Map<String, dynamic> json) => MyCard(
        id: json["id"],
        name: json["name"],
        cardId: json["card_id"],
        cardNumber: json["card_number"],
        expiry: json["expiry"],
        cvv: json["cvv"],
        balance: json["balance"]?.toDouble() ?? 0.0,
        cardBackDetails: json["card_back_details"],
        siteTitle: json["site_title"],
        siteLogo: json["site_logo"],
        siteFav: json["site_fav"],
        status: json["status"],
        isDefault: json["is_default"] ?? false,
        statusInfo: MyCardStatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "name": name,
        "card_id": cardId,
        "card_number": cardNumber,
        "expiry": expiry,
        "cvv": cvv,
        "balance": balance,
        "card_back_details": cardBackDetails,
        "site_title": siteTitle,
        "site_logo": siteLogo,
        "site_fav": siteFav,
        "status": status,
        "is_default": isDefault,
        "status_info": statusInfo.toJson(),
      };
}

class MyCardStatusInfo {
  int block;
  int unblock;

  MyCardStatusInfo({
    required this.block,
    required this.unblock,
  });

  factory MyCardStatusInfo.fromJson(Map<String, dynamic> json) =>
      MyCardStatusInfo(
        block: json["block"],
        unblock: json["unblock"],
      );

  Map<String, dynamic> toJson() => {
        "block": block,
        "unblock": unblock,
      };
}

class Transaction {
  int id;
  String trx;
  String transactinType;
  String requestAmount;
  String payable;
  String totalCharge;
  String cardAmount;
  String cardNumber;
  String currentBalance;
  String status;
  DateTime dateTime;
  TransactionStatusInfo statusInfo;

  Transaction({
    required this.id,
    required this.trx,
    required this.transactinType,
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

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json["id"],
        trx: json["trx"],
        transactinType: json["transactin_type"],
        requestAmount: json["request_amount"],
        payable: json["payable"],
        totalCharge: json["total_charge"],
        cardAmount: json["card_amount"],
        cardNumber: json["card_number"],
        currentBalance: json["current_balance"],
        status: json["status"],
        dateTime: DateTime.parse(json["date_time"]),
        statusInfo: TransactionStatusInfo.fromJson(json["status_info"]),
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "trx": trx,
        "transactin_type": transactinType,
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

class TransactionStatusInfo {
  int success;
  int pending;
  int rejected;

  TransactionStatusInfo({
    required this.success,
    required this.pending,
    required this.rejected,
  });

  factory TransactionStatusInfo.fromJson(Map<String, dynamic> json) =>
      TransactionStatusInfo(
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
  String firstname;
  String lastname;
  String username;
  String email;
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
  dynamic deviceId;
  dynamic emailVerifiedAt;
  dynamic deletedAt;
  DateTime createdAt;
  DateTime updatedAt;
  dynamic sudoCustomer;
  dynamic sudoAccount;
  dynamic strowalletCustomer;
  dynamic stripeCardHolders;
  dynamic stripeConnectedAccount;
  String fullname;
  String userImage;
  Status stringStatus;
  String lastLogin;
  Status kycStringStatus;

  User({
    required this.id,
    required this.firstname,
    required this.lastname,
    required this.username,
    required this.email,
    this.mobileCode,
    this.mobile,
    this.fullMobile,
    required this.refferalUserId,
    this.image,
    required this.status,
    required this.address,
    required this.emailVerified,
    required this.smsVerified,
    required this.kycVerified,
    required this.verCode,
    required this.verCodeSendAt,
    required this.twoFactorVerified,
    required this.deviceId,
    required this.emailVerifiedAt,
    required this.deletedAt,
    required this.createdAt,
    required this.updatedAt,
    required this.sudoCustomer,
    required this.sudoAccount,
    this.strowalletCustomer,
    required this.stripeCardHolders,
    required this.stripeConnectedAccount,
    required this.fullname,
    required this.userImage,
    required this.stringStatus,
    required this.lastLogin,
    required this.kycStringStatus,
  });

  factory User.fromJson(Map<String, dynamic> json) => User(
        id: json["id"],
        firstname: json["firstname"],
        lastname: json["lastname"],
        username: json["username"],
        email: json["email"],
        mobileCode: json["mobile_code"] ?? "",
        mobile: json["mobile"] ?? "",
        fullMobile: json["full_mobile"] ?? "",
        refferalUserId: json["refferal_user_id"],
        image: json["image"] ?? "",
        status: json["status"],
        address: Address.fromJson(json["address"]),
        emailVerified: json["email_verified"],
        smsVerified: json["sms_verified"],
        kycVerified: json["kyc_verified"],
        verCode: json["ver_code"],
        verCodeSendAt: json["ver_code_send_at"],
        twoFactorVerified: json["two_factor_verified"],
        deviceId: json["device_id"],
        emailVerifiedAt: json["email_verified_at"],
        deletedAt: json["deleted_at"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        sudoCustomer: json["sudo_customer"],
        sudoAccount: json["sudo_account"],
        strowalletCustomer: json["strowallet_customer"] ?? "",
        stripeCardHolders: json["stripe_card_holders"],
        stripeConnectedAccount: json["stripe_connected_account"],
        fullname: json["fullname"],
        userImage: json["userImage"],
        stringStatus: Status.fromJson(json["stringStatus"]),
        lastLogin: json["lastLogin"],
        kycStringStatus: Status.fromJson(json["kycStringStatus"]),
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
        "device_id": deviceId,
        "email_verified_at": emailVerifiedAt,
        "deleted_at": deletedAt,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "sudo_customer": sudoCustomer,
        "sudo_account": sudoAccount,
        "strowallet_customer": strowalletCustomer!.toJson(),
        "stripe_card_holders": stripeCardHolders,
        "stripe_connected_account": stripeConnectedAccount,
        "fullname": fullname,
        "userImage": userImage,
        "stringStatus": stringStatus.toJson(),
        "lastLogin": lastLogin,
        "kycStringStatus": kycStringStatus.toJson(),
      };
}

class Address {
  String country;
  String state;
  String city;
  String zip;
  String address;

  Address({
    required this.country,
    required this.state,
    required this.city,
    required this.zip,
    required this.address,
  });

  factory Address.fromJson(Map<String, dynamic> json) => Address(
        country: json["country"],
        state: json["state"],
        city: json["city"],
        zip: json["zip"],
        address: json["address"],
      );

  Map<String, dynamic> toJson() => {
        "country": country,
        "state": state,
        "city": city,
        "zip": zip,
        "address": address,
      };
}

class Status {
  String statusClass;
  String value;

  Status({
    required this.statusClass,
    required this.value,
  });

  factory Status.fromJson(Map<String, dynamic> json) => Status(
        statusClass: json["class"],
        value: json["value"],
      );

  Map<String, dynamic> toJson() => {
        "class": statusClass,
        "value": value,
      };
}

class StrowalletCustomer {
  String bvn;
  String customerEmail;
  String firstName;
  String lastName;
  String phoneNumber;
  String city;
  String state;
  String country;
  String line1;
  String zipCode;
  String houseNumber;
  String idNumber;
  String idType;
  String idImage;
  String userPhoto;
  DateTime dateOfBirth;
  String bitvcardCustomerId;
  String cardBrand;

  StrowalletCustomer({
    required this.bvn,
    required this.customerEmail,
    required this.firstName,
    required this.lastName,
    required this.phoneNumber,
    required this.city,
    required this.state,
    required this.country,
    required this.line1,
    required this.zipCode,
    required this.houseNumber,
    required this.idNumber,
    required this.idType,
    required this.idImage,
    required this.userPhoto,
    required this.dateOfBirth,
    required this.bitvcardCustomerId,
    required this.cardBrand,
  });

  factory StrowalletCustomer.fromJson(Map<String, dynamic> json) =>
      StrowalletCustomer(
        bvn: json["bvn"],
        customerEmail: json["customerEmail"],
        firstName: json["firstName"],
        lastName: json["lastName"],
        phoneNumber: json["phoneNumber"],
        city: json["city"],
        state: json["state"],
        country: json["country"],
        line1: json["line1"],
        zipCode: json["zipCode"],
        houseNumber: json["houseNumber"],
        idNumber: json["idNumber"],
        idType: json["idType"],
        idImage: json["idImage"],
        userPhoto: json["userPhoto"],
        dateOfBirth: DateTime.parse(json["dateOfBirth"]),
        bitvcardCustomerId: json["bitvcard_customer_id"],
        cardBrand: json["card_brand"],
      );

  Map<String, dynamic> toJson() => {
        "bvn": bvn,
        "customerEmail": customerEmail,
        "firstName": firstName,
        "lastName": lastName,
        "phoneNumber": phoneNumber,
        "city": city,
        "state": state,
        "country": country,
        "line1": line1,
        "zipCode": zipCode,
        "houseNumber": houseNumber,
        "idNumber": idNumber,
        "idType": idType,
        "idImage": idImage,
        "userPhoto": userPhoto,
        "dateOfBirth":
            "${dateOfBirth.year.toString().padLeft(4, '0')}-${dateOfBirth.month.toString().padLeft(2, '0')}-${dateOfBirth.day.toString().padLeft(2, '0')}",
        "bitvcard_customer_id": bitvcardCustomerId,
        "card_brand": cardBrand,
      };
}

class UserWallet {
  dynamic balance;
  String currency;

  UserWallet({
    this.balance,
    required this.currency,
  });

  factory UserWallet.fromJson(Map<String, dynamic> json) => UserWallet(
        balance: json["balance"]?.toDouble() ?? 0.0,
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
