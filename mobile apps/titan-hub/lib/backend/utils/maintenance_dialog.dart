import 'package:qrpay/utils/basic_screen_imports.dart';
import 'package:restart_app/restart_app.dart';

import '../model/common/maintenance_model.dart';

class SystemMaintenanceController extends GetxController {
  RxBool maintenanceStatus = false.obs;
}

class MaintenanceDialog {
  show({required MaintenanceModel maintenanceModel}) {
    Get.dialog(
      WillPopScope(
        onWillPop: () async {
          Restart.restartApp();
          return false;
        },
        child: Dialog(
          insetPadding: EdgeInsets.zero,
          child: Container(
            width: double.infinity,
            height: double.infinity,
            color: Get.isDarkMode
                ? CustomColor.primaryDarkScaffoldBackgroundColor
                : CustomColor.primaryLightScaffoldBackgroundColor,
            padding: EdgeInsets.symmetric(
              horizontal: Dimensions.paddingHorizontalSize * 0.8,
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  margin: EdgeInsets.symmetric(
                    vertical: Dimensions.paddingVerticalSize * 0.5,
                  ),
                  child: Image.network(
                    "${maintenanceModel.data.baseUrl}/${maintenanceModel.data.imagePath}/${maintenanceModel.data.image}",
                  ),
                ),
                TitleHeading3Widget(
                  text: maintenanceModel.data.title,
                  textAlign: TextAlign.center,
                ),
                Container(
                  margin: EdgeInsets.symmetric(
                    vertical: Dimensions.paddingVerticalSize * 0.5,
                  ),
                  child: TitleHeading4Widget(
                    text: maintenanceModel.data.details,
                    textAlign: TextAlign.center,
                  ),
                ),
                PrimaryButton(
                  title: Strings.restart,
                  onPressed: () {
                    Restart.restartApp();
                  },
                )
              ],
            ),
          ),
        ),
      ),
      barrierDismissible: false,
    );
  }
}
