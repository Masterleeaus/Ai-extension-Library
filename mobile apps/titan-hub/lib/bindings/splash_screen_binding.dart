import 'package:get/get.dart';

import '../controller/auth/registration/kyc_form_controller.dart';
import '../controller/splsh_onboard/splash_screen_controller.dart';

class SplashBinding extends Bindings {
  @override
  void dependencies() {
    Get.put(SplashController());
    Get.put(BasicDataController());
  }
}
