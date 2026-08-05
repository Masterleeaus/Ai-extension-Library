import 'package:get/get.dart';
import 'package:qrpay/backend/model/common/common_success_model.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../backend/services/api_services.dart';
import '../../routes/routes.dart';

class LogOutController extends GetxController {
  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;
  late CommonSuccessModel _logOutModel;

  CommonSuccessModel get dashBoardModel => _logOutModel;

  Future<CommonSuccessModel> logOutProcess() async {
    _isLoading.value = true;
    update();

    await ApiServices.logOut().then((value) {
      _logOutModel = value!;
      LocalStorages.logout();
      Get.offNamedUntil(Routes.signInScreen, (route) => false);
      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    update();
    return _logOutModel;
  }
}
