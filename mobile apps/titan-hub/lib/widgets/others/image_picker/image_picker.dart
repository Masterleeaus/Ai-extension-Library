import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:qrpay/controller/profile/update_profile_controller.dart';
import 'package:qrpay/views/others/custom_image_widget.dart';

import '../../../backend/services/api_endpoint.dart';
import '../../../backend/utils/custom_snackbar.dart';
import '../../../controller/others/image_picker_controller.dart';
import '../../../custom_assets/assets.gen.dart';
import '../../../utils/custom_color.dart';
import '../../../utils/dimensions.dart';

File? imageFile;

class ImagePickerWidget extends StatelessWidget {
  ImagePickerWidget({super.key});
  final controller = Get.put(UpdateProfileController());
  final imgController = Get.put(ProfileImagePicker());

  // image picker function
  Future pickImage(imageSource) async {
    try {
      final image = await ImagePicker().pickImage(
        source: imageSource,
        imageQuality: 40, // define image quality
        maxHeight: 600, // image height
        maxWidth: 600, // image width
      );
      if (image == null) return;

      imageFile = File(image.path);
      imgController.setImagePath(imageFile!.path);
    } on PlatformException catch (e) {
      CustomSnackBar.error('Error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      return Center(
        child: imgController.isImagePathSet.value == true
            ? GestureDetector(
                onTap: () {
                  showModalBottomSheet(
                    context: context,
                    builder: (context) =>
                        _imagePickerBottomSheetWidget(context),
                  );
                },
                child: Container(
                  margin: EdgeInsets.only(
                    top: Dimensions.marginSizeVertical,
                    bottom: Dimensions.marginSizeVertical,
                  ),
                  height: Dimensions.heightSize * 8.3,
                  width: Dimensions.widthSize * 11.5,
                  decoration: BoxDecoration(
                      borderRadius:
                          BorderRadius.circular(Dimensions.radius * 1.5),
                      color: CustomColor.primaryLightColor,
                      border: Border.all(
                          color: CustomColor.primaryLightColor, width: 5),
                      image: DecorationImage(
                          image: FileImage(
                            File(
                              imgController.imagePath.value,
                            ),
                          ),
                          fit: BoxFit.cover)),
                ),
              )
            : _userImageWidget(context),
      );
    });
  }

  _imagePickerBottomSheetWidget(BuildContext context) {
    return Container(
      width: double.infinity,
      height: MediaQuery.of(context).size.height * 0.15,
      margin: EdgeInsets.all(Dimensions.marginSizeVertical * 0.5),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: IconButton(
                onPressed: () {
                  Get.back();
                  pickImage(ImageSource.gallery);
                },
                icon: Icon(
                  Icons.image,
                  color: CustomColor.primaryLightColor,
                  size: 50,
                )),
          ),
          Padding(
            padding: EdgeInsets.all(Dimensions.paddingSize),
            child: IconButton(
                onPressed: () {
                  Get.back();
                  pickImage(ImageSource.camera);
                },
                icon: Icon(
                  Icons.camera,
                  color: CustomColor.primaryLightColor,
                  size: 50,
                )),
          ),
        ],
      ),
    );
  }

  _userImageWidget(BuildContext context) {
    var data = controller.profileModel.data;
    final image =
        '${ApiEndpoint.mainDomain}/${data.imagePath}/${data.user.image}';
    final defaultImage =
        '${ApiEndpoint.mainDomain}/${data.imagePath}/${data.defaultImage}';
    return Stack(
      children: [
        Center(
          child: Container(
            margin: EdgeInsets.only(
              top: Dimensions.marginSizeVertical,
              bottom: Dimensions.marginSizeVertical,
            ),
            height: Dimensions.heightSize * 8.3,
            width: Dimensions.widthSize * 11.5,
            decoration: BoxDecoration(
              borderRadius: BorderRadius.circular(Dimensions.radius * 1.5),
              color: CustomColor.primaryLightColor,
              border:
                  Border.all(color: CustomColor.primaryLightColor, width: 5),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(Dimensions.radius),
              child: FadeInImage(
                height: double.infinity,
                width: double.infinity,
                fit: BoxFit.cover,
                image: NetworkImage(
                    data.imagePath.isNotEmpty ? image : defaultImage),
                placeholder: AssetImage(
                  Assets.clipart.user.path,
                ),
                imageErrorBuilder: (context, error, stackTrace) {
                  return Image.asset(
                    Assets.clipart.user.path,
                    height: double.infinity,
                    width: double.infinity,
                    fit: BoxFit.cover,
                  );
                },
              ),
            ),
          ),
        ),
        GestureDetector(
          onTap: () {
            showModalBottomSheet(
              context: context,
              builder: (context) => _imagePickerBottomSheetWidget(context),
            );
          },
          child: Center(
            child: Container(
              margin: EdgeInsets.only(top: Dimensions.marginSizeVertical * 2.6),
              child: CustomImageWidget(
                path: Assets.icon.camera,
                color: CustomColor.whiteColor,
                height: Dimensions.heightSize * 2,
              ),
            ),
          ),
        ),
      ],
    );
  }
}
