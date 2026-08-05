class ErrorResponse {
  ErrorResponse({
    required this.message,
  });

  late final Message message;

  ErrorResponse.fromJson(Map<String, dynamic> json) {
    message = Message.fromJson(json['message']);
  }

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['message'] = message.toJson();
    return data;
  }
}

class Message {
  Message({
    required this.error,
  });

  late final List<String> error;

  Message.fromJson(Map<String, dynamic> json) {
    // Handle null values gracefully
    error = json['error'] != null ? List<String>.from(json['error']) : [];
  }

  Map<String, dynamic> toJson() {
    final data = <String, dynamic>{};
    data['error'] = error;
    return data;
  }
}
