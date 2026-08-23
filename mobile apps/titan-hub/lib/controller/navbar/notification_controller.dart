

import 'package:get/get.dart';

import '../../backend/model/bottom_navbar_model/notification_model.dart';
import '../../backend/services/api_services.dart';

class NotificationController extends GetxController{

  @override
  void onInit() {
    getNotificationData();
    super.onInit();
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  late NotificationModel _notificationModelData;
  NotificationModel get notificationModelData => _notificationModelData;

  Future<NotificationModel> getNotificationData() async {
    _isLoading.value = true;
    update();

    // calling  from api service
    await ApiServices.getNotificationAPi().then((value) {
      _notificationModelData = value!;

      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    update();
    return _notificationModelData;
  }
}