import 'dart:convert';

BankAccountCheckModel bankAccountCheckModelFromJson(String str) =>
    BankAccountCheckModel.fromJson(json.decode(str));

String bankAccountCheckModelToJson(BankAccountCheckModel data) =>
    json.encode(data.toJson());

class BankAccountCheckModel {
  Message message;
  Data data;

  BankAccountCheckModel({
    required this.message,
    required this.data,
  });

  factory BankAccountCheckModel.fromJson(Map<String, dynamic> json) =>
      BankAccountCheckModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  List<BankBranchInfo> bankBranches;

  Data({
    required this.bankBranches,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        bankBranches: List<BankBranchInfo>.from(
            json["bank_branches"].map((x) => BankBranchInfo.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "bank_branches":
            List<dynamic>.from(bankBranches.map((x) => x.toJson())),
      };
}

class BankBranchInfo {
  int id;
  String branchCode;
  String branchName;
  dynamic swiftCode;
  dynamic bic;
  int bankId;

  BankBranchInfo({
    required this.id,
    required this.branchCode,
    required this.branchName,
    required this.swiftCode,
    required this.bic,
    required this.bankId,
  });

  factory BankBranchInfo.fromJson(Map<String, dynamic> json) => BankBranchInfo(
        id: json["id"],
        branchCode: json["branch_code"],
        branchName: json["branch_name"],
        swiftCode: json["swift_code"],
        bic: json["bic"],
        bankId: json["bank_id"],
      );

  Map<String, dynamic> toJson() => {
        "id": id,
        "branch_code": branchCode,
        "branch_name": branchName,
        "swift_code": swiftCode,
        "bic": bic,
        "bank_id": bankId,
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
