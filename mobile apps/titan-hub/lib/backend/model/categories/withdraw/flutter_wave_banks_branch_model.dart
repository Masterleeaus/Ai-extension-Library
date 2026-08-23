import 'dart:convert';

BankBranchesModel bankBranchesModelFromJson(String str) =>
    BankBranchesModel.fromJson(json.decode(str));

String bankBranchesModelToJson(BankBranchesModel data) =>
    json.encode(data.toJson());

class BankBranchesModel {
  Message message;
  Data data;

  BankBranchesModel({
    required this.message,
    required this.data,
  });

  factory BankBranchesModel.fromJson(Map<String, dynamic> json) =>
      BankBranchesModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  List<BankBranch> bankBranches;

  Data({
    required this.bankBranches,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        bankBranches: List<BankBranch>.from(
            json["bank_branches"].map((x) => BankBranch.fromJson(x))),
      );

  Map<String, dynamic> toJson() => {
        "bank_branches":
            List<dynamic>.from(bankBranches.map((x) => x.toJson())),
      };
}

class BankBranch {
  int id;
  String branchCode;
  String branchName;
  dynamic swiftCode;
  dynamic bic;
  int bankId;

  BankBranch({
    required this.id,
    required this.branchCode,
    required this.branchName,
    required this.swiftCode,
    required this.bic,
    required this.bankId,
  });

  factory BankBranch.fromJson(Map<String, dynamic> json) => BankBranch(
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
