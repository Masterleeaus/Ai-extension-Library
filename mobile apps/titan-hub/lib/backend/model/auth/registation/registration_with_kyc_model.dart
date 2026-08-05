// To parse this JSON data, do
//
//     final registrationWithKycModel = registrationWithKycModelFromJson(jsonString);

import 'dart:convert';

RegistrationWithKycModel registrationWithKycModelFromJson(String str) => RegistrationWithKycModel.fromJson(json.decode(str));

String registrationWithKycModelToJson(RegistrationWithKycModel data) => json.encode(data.toJson());

class RegistrationWithKycModel {
  Message message;
  Data data;

  RegistrationWithKycModel({
    required this.message,
    required this.data,
  });

  factory RegistrationWithKycModel.fromJson(Map<String, dynamic> json) => RegistrationWithKycModel(
    message: Message.fromJson(json["message"]),
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message.toJson(),
    "data": data.toJson(),
  };
}

class Data {
  String token;
  // User user;

  Data({
    required this.token,
    // required this.user,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    token: json["token"],
    // user: User.fromJson(json["user"]),
  );

  Map<String, dynamic> toJson() => {
    "token": token,
    // "user": user.toJson(),
  };
}

// class User {
//   // String firstname;
//   // String lastname;
//   // String email;
//   // String mobile;
//   // String mobileCode;
//   // String fullMobile;
//   // String username;
//   // Address address;
//   // int status;
//   int emailVerified;
//   int smsVerified;
//   int kycVerified;
//   DateTime updatedAt;
//   DateTime createdAt;
//   int id;
//   String fullname;
//   String userImage;
//   StringStatus stringStatus;
//   String lastLogin;
//   StringStatus kycStringStatus;
//
//   User({
//     required this.firstname,
//     required this.lastname,
//     required this.email,
//     required this.mobile,
//     required this.mobileCode,
//     required this.fullMobile,
//     required this.username,
//     required this.address,
//     required this.status,
//     required this.emailVerified,
//     required this.smsVerified,
//     required this.kycVerified,
//     required this.updatedAt,
//     required this.createdAt,
//     required this.id,
//     required this.fullname,
//     required this.userImage,
//     required this.stringStatus,
//     required this.lastLogin,
//     required this.kycStringStatus,
//   });
//
//   factory User.fromJson(Map<String, dynamic> json) => User(
//     firstname: json["firstname"],
//     lastname: json["lastname"],
//     email: json["email"],
//     mobile: json["mobile"],
//     mobileCode: json["mobile_code"],
//     fullMobile: json["full_mobile"],
//     username: json["username"],
//     address: Address.fromJson(json["address"]),
//     status: json["status"],
//     emailVerified: json["email_verified"],
//     smsVerified: json["sms_verified"],
//     kycVerified: json["kyc_verified"],
//     updatedAt: DateTime.parse(json["updated_at"]),
//     createdAt: DateTime.parse(json["created_at"]),
//     id: json["id"],
//     fullname: json["fullname"],
//     userImage: json["userImage"],
//     stringStatus: StringStatus.fromJson(json["stringStatus"]),
//     lastLogin: json["lastLogin"],
//     kycStringStatus: StringStatus.fromJson(json["kycStringStatus"]),
//   );
//
//   Map<String, dynamic> toJson() => {
//     "firstname": firstname,
//     "lastname": lastname,
//     "email": email,
//     "mobile": mobile,
//     "mobile_code": mobileCode,
//     "full_mobile": fullMobile,
//     "username": username,
//     "address": address.toJson(),
//     "status": status,
//     "email_verified": emailVerified,
//     "sms_verified": smsVerified,
//     "kyc_verified": kycVerified,
//     "updated_at": updatedAt.toIso8601String(),
//     "created_at": createdAt.toIso8601String(),
//     "id": id,
//     "fullname": fullname,
//     "userImage": userImage,
//     "stringStatus": stringStatus.toJson(),
//     "lastLogin": lastLogin,
//     "kycStringStatus": kycStringStatus.toJson(),
//   };
// }

// class Address {
//   String address;
//   String city;
//   String zip;
//   String country;
//   String state;
//
//   Address({
//     required this.address,
//     required this.city,
//     required this.zip,
//     required this.country,
//     required this.state,
//   });
//
//   factory Address.fromJson(Map<String, dynamic> json) => Address(
//     address: json["address"],
//     city: json["city"],
//     zip: json["zip"],
//     country: json["country"],
//     state: json["state"],
//   );
//
//   Map<String, dynamic> toJson() => {
//     "address": address,
//     "city": city,
//     "zip": zip,
//     "country": country,
//     "state": state,
//   };
// }
//
// class StringStatus {
//   String stringStatusClass;
//   String value;
//
//   StringStatus({
//     required this.stringStatusClass,
//     required this.value,
//   });
//
//   factory StringStatus.fromJson(Map<String, dynamic> json) => StringStatus(
//     stringStatusClass: json["class"],
//     value: json["value"],
//   );
//
//   Map<String, dynamic> toJson() => {
//     "class": stringStatusClass,
//     "value": value,
//   };
// }

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
