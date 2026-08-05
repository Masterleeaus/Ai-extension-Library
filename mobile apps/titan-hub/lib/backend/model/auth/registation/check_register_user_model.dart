
class CheckRegisterUserModel {
    Message message;

    CheckRegisterUserModel({
        required this.message,
    });

    factory CheckRegisterUserModel.fromJson(Map<String, dynamic> json) => CheckRegisterUserModel(
        message: Message.fromJson(json["message"]),
    );

    Map<String, dynamic> toJson() => {
        "message": message.toJson(),
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
