
class StrowalletChargeModel {
  Message message;
  Data data;

  StrowalletChargeModel({
    required this.message,
    required this.data,
  });

  factory StrowalletChargeModel.fromJson(Map<String, dynamic> json) =>
      StrowalletChargeModel(
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
  CardCharge cardCharge;

  Data({
    required this.baseCurr,
    required this.cardCharge,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseCurr: json["base_curr"],
        cardCharge: CardCharge.fromJson(json["cardCharge"]),
      );

  Map<String, dynamic> toJson() => {
        "base_curr": baseCurr,
        "cardCharge": cardCharge.toJson(),
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
     this.fixedCharge,
     this.percentCharge,
     this.minLimit,
     this.maxLimit,
  });

  factory CardCharge.fromJson(Map<String, dynamic> json) => CardCharge(
        id: json["id"],
        slug: json["slug"],
        title: json["title"],
        fixedCharge: json["fixed_charge"]?.toDouble()??0.0,
        percentCharge: json["percent_charge"]?.toDouble()??0.0,
        minLimit: json["min_limit"]?.toDouble()??0.0,
        maxLimit: json["max_limit"]?.toDouble()??0.0,
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
