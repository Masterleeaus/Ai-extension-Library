import 'package:get/get.dart';

import '../../backend/local_storage/local_storage.dart';
import '../../routes/routes.dart';
import '../app_settings/app_settings_controller.dart';
import 'navigator_plug.dart';

class SplashController extends GetxController {
  final navigatorPlug = NavigatorPlug();

  @override
  void onReady() {
    super.onReady();
    navigatorPlug.startListening(
      seconds: 3,
      onChanged: () {
        LocalStorages.isLoggedIn()
            //todo change route to sign in screen
            ? Get.offAndToNamed(Routes.bottomNavBarScreen)
            : Get.offAndToNamed(
                LocalStorages.isOnBoardDone()
                    ? Routes.signInScreen
                    : Get.find<AppSettingsController>()
                            .appSettingsModel
                            .data
                            .appSettings
                            .user
                            .onboardScreen
                            .isEmpty
                        ? Routes.signInScreen
                        : Routes.onboardScreen,
              );
      },
    );
  }

  @override
  void onClose() {
    navigatorPlug.stopListening();
    super.onClose();
  }
}
