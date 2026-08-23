import 'dart:convert';

PusherBeamsModel pusherBeamsModelFromJson(String str) =>
    PusherBeamsModel.fromJson(json.decode(str));

String pusherBeamsModelToJson(PusherBeamsModel data) =>
    json.encode(data.toJson());

class PusherBeamsModel {
  final String token;

  PusherBeamsModel({
    required this.token,
  });

  factory PusherBeamsModel.fromJson(Map<String, dynamic> json) =>
      PusherBeamsModel(
        token: json["token"],
      );

  Map<String, dynamic> toJson() => {
        "token": token,
      };
}
