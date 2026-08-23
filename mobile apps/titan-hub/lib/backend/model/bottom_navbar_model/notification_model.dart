
class NotificationModel {
  Message message;
  Data data;

  NotificationModel({
    required this.message,
    required this.data,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) => NotificationModel(
    message: Message.fromJson(json["message"]),
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message.toJson(),
    "data": data.toJson(),
  };
}

class Data {
  List<Notification> notifications;

  Data({
    required this.notifications,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    notifications: List<Notification>.from(json["notifications"].map((x) => Notification.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "notifications": List<dynamic>.from(notifications.map((x) => x.toJson())),
  };
}

class Notification {
  int id;
  String type;
  String title;
  String message;
  DateTime createdAt;
  DateTime updatedAt;

  Notification({
    required this.id,
    required this.type,
    required this.title,
    required this.message,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Notification.fromJson(Map<String, dynamic> json) => Notification(
    id: json["id"],
    type: json["type"],
    title: json["title"],
    message: json["message"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "type": type,
    "title": title,
    "message": message,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
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
