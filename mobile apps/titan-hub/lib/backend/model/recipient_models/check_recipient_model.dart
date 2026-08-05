
import 'dart:convert';

CheckRecipientModel checkRecipientModelFromJson(String str) => CheckRecipientModel.fromJson(json.decode(str));

String checkRecipientModelToJson(CheckRecipientModel data) => json.encode(data.toJson());

class CheckRecipientModel {
  Message message;
  Data data;

  CheckRecipientModel({
    required this.message,
    required this.data,
  });

  factory CheckRecipientModel.fromJson(Map<String, dynamic> json) => CheckRecipientModel(
    message: Message.fromJson(json["message"]),
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message.toJson(),
    "data": data.toJson(),
  };
}

class Data {
  User user;

  Data({
    required this.user,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    user: User.fromJson(json["user"]),
  );

  Map<String, dynamic> toJson() => {
    "user": user.toJson(),
  };
}

class User {
  int id;
  String firstname;
  String lastname;
  String username;
  String email;
  String mobileCode;
  String mobile;
  String fullMobile;
  int status;
  Address address;
  int emailVerified;
  int smsVerified;
  int kycVerified;
  String fullname;
  String userImage;
  StringStatus stringStatus;
  String lastLogin;
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
    required this.status,
    required this.address,
    required this.emailVerified,
    required this.smsVerified,
    required this.kycVerified,
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
    mobileCode: json["mobile_code"],
    mobile: json["mobile"],
    fullMobile: json["full_mobile"],
    status: json["status"],
    address: Address.fromJson(json["address"]),
    emailVerified: json["email_verified"],
    smsVerified: json["sms_verified"],
    kycVerified: json["kyc_verified"],
    fullname: json["fullname"],
    userImage: json["userImage"],
    stringStatus: StringStatus.fromJson(json["stringStatus"]),
    lastLogin: json["lastLogin"],
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
    "status": status,
    "address": address.toJson(),
    "email_verified": emailVerified,
    "sms_verified": smsVerified,
    "kyc_verified": kycVerified,
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

class StringStatus {
  String stringStatusClass;
  String value;

  StringStatus({
    required this.stringStatusClass,
    required this.value,
  });

  factory StringStatus.fromJson(Map<String, dynamic> json) => StringStatus(
    stringStatusClass: json["class"],
    value: json["value"],
  );

  Map<String, dynamic> toJson() => {
    "class": stringStatusClass,
    "value": value,
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
