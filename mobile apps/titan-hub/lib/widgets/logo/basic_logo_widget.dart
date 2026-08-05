import 'package:qrpay/controller/app_settings/app_settings_controller.dart';

import '../../utils/basic_screen_imports.dart';

class BasicLogoWidget extends StatelessWidget {
  final bool isWhite, isDashBoard;
  final double? height;
  final double? width;
  BasicLogoWidget(
      {super.key,
      this.isWhite = false,
      this.isDashBoard = false,
      this.height,
      this.width});
  final controller = Get.find<AppSettingsController>();
  @override
  Widget build(BuildContext context) {
    return Obx(
      () => controller.isLoading
          ? const Text('')
          : Image.network(
              isWhite
                  ? controller.appBasicLogoWhite.value
                  : controller.appBasicLogoDark.value,
              width: width ?? MediaQuery.of(context).size.width * 0.52,
              height: height ??
                  MediaQuery.of(context).size.height *
                      (isDashBoard ? 0.055 : 0.1),
            ),
    );
  }
}
