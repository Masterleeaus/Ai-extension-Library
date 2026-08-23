
class CheckAgentWithQrCodeModel {
  Message message;
  Data data;

  CheckAgentWithQrCodeModel({
    required this.message,
    required this.data,
  });

  factory CheckAgentWithQrCodeModel.fromJson(Map<String, dynamic> json) =>
      CheckAgentWithQrCodeModel(
        message: Message.fromJson(json["message"]),
        data: Data.fromJson(json["data"]),
      );

  Map<String, dynamic> toJson() => {
        "message": message.toJson(),
        "data": data.toJson(),
      };
}

class Data {
  String agentEmail;

  Data({
    required this.agentEmail,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
        agentEmail: json["agent_email"],
      );

  Map<String, dynamic> toJson() => {
        "agent_email": agentEmail,
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
