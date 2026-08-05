import 'dart:convert';

MaintenanceModel maintenanceModelFromJson(String str) =>
    MaintenanceModel.fromJson(json.decode(str));

String maintenanceModelToJson(MaintenanceModel data) =>
    json.encode(data.toJson());

class MaintenanceModel {
  Message message;
  Data data;

  MaintenanceModel({
    required this.message,
    required this.data,
  });

  factory MaintenanceModel.fromJson(Map<String, dynamic> json) =>
      MaintenanceModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String baseUrl;
  String imagePath;
  String image;
  bool status;
  String title;
  String details;

  Data({
    required this.baseUrl,
    required this.imagePath,
    required this.image,
    required this.status,
    required this.title,
    required this.details,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        baseUrl: json["base_url"],
        imagePath: json["image_path"],
        image: json["image"],
        status: json["status"],
        title: json["title"],
        details: json["details"],
      );

  Map<String, dynamic> toJson() => {
        "base_url": baseUrl,
        "image_path": imagePath,
        "image": image,
        "status": status,
        "title": title,
        "details": details,
      };
}

class Message {
  List<String> error;

  Message({
    required this.error,
  });

  factory Message.fromJson(Map<String, dynamic> json) => Message(
        error: List<String>.from(json["error"].map((x) => x)),
      );

  Map<String, dynamic> toJson() => {
        "error": List<dynamic>.from(error.map((x) => x)),
      };
}
