import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/utils/responsive_layout.dart';

import '../../backend/utils/custom_loading_api.dart';
import '../../controller/app_settings/app_settings_controller.dart';
import '../../language/language_controller.dart';
import '../../utils/custom_color.dart';

class SplashScreen extends StatelessWidget {
  SplashScreen({
    super.key,
  });
  final controller = Get.find<AppSettingsController>();
  final languageController = Get.find<LanguageController>();
  @override
  Widget build(BuildContext context) {
    return ResponsiveLayout(
      mobileScaffold: Scaffold(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        body: Obx(
          () => controller.isLoading
              ? const CustomLoadingAPI()
              : Center(
                  child: Stack(
                    alignment: Alignment.bottomCenter,
                    children: [
                      CachedNetworkImage(
                        imageUrl: controller.splashImagePath.value,
                        placeholder: (context, url) => const Text(''),
                        errorWidget: (context, url, error) => const Text(''),
                      ),
                      Visibility(
                        visible: languageController.isLoading &&
                            controller.isVisible.value,
                        child: Padding(
                          padding: EdgeInsets.only(
                            bottom: MediaQuery.sizeOf(context).height * 0.2,
                            left: MediaQuery.sizeOf(context).width * 0.15,
                            right: MediaQuery.sizeOf(context).width * 0.15,
                          ),
                          child: LinearProgressIndicator(
                            color:
                                CustomColor.primaryLightColor.withOpacity(0.8),
                            backgroundColor: Colors.transparent,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
        ),
      ),
    );
  }
}
